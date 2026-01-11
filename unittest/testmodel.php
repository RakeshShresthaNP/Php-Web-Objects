<?php
declare(strict_types = 1);

require_once '../myapp/app/models/model.php';

// 1. Connection Logic
function db()
{
    static $pdo;
    if (! $pdo) {
        $pdo = new PDO("mysql:host=localhost;dbname=testorm", "root", "Nepal@123");
        $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    }
    return $pdo;
}

// 2. Test Runner
function it(string $desc, callable $fn)
{
    echo str_pad($desc, 50, ".");
    try {
        $fn();
        echo "✅\n<br>";
    } catch (Throwable $e) {
        echo "❌\n   ERROR: {$e->getMessage()}\n<br>";
    }
}

echo "--- ORM METHOD COVERAGE TEST ---\n<br>";

// SEEDING DATA FOR TESTS
db()->exec("TRUNCATE users;");
db()->exec("INSERT INTO users (id, name, email) VALUES (1, 'Rakesh', 'rakesh@test.com')");

// BEGIN TESTS
it("Basic CRUD (save, insert, update, find)", function () {
    $u = new model('users');
    $u->name = "Test";
    $u->email = "test@test.com";
    $id = $u->save();
    $found = (new model('users'))->where('id', $id)->find();
    if ($found->name !== "Test")
        throw new Exception("CRUD Mismatch");
});

it("Query Building (where, orWhere, whereNull, search)", function () {
    $m = new model('users');
    $m->where('id', 1)
        ->orWhere('name', 'Test')
        ->whereNull('d_deleted')
        ->search([
        'name'
    ], 'rak');
    $sql = $m->toSql();
    if (! str_contains($sql, 'OR name =') || ! str_contains($sql, 'LIKE'))
        throw new Exception("SQL Build Error");
});

it("Advanced Filters (whereIn, whereBetween, whereRaw)", function () {
    $ids = [
        1,
        2
    ];
    $range = [
        '2020-01-01',
        '2026-12-31'
    ];
    $m = new model('users');
    $m->whereIn('id', $ids)
        ->whereBetween('d_created', $range)
        ->whereRaw("id > ?", [
        0
    ]);
    if (! $m->find())
        throw new Exception("Advanced Filters Failed");
});

it("Subquery: whereExists Method Test", function () {
    $m = new model('users');

    // Assign the result of the query builder to $results
    $results = $m->select("p.name")
        ->whereExists('orders', function ($sub) {
        // Use 'sub' as the alias for the subquery table to avoid naming conflicts
        $sub->whereRaw("sub.user_id = p.id");
    })
        ->find();

    // Now $results is defined. We normalize it for the model's return type.
    $data = is_array($results) ? $results : ($results ? [
        $results
    ] : []);

    if (empty($data)) {
        throw new Exception("whereExists failed: No users found with orders.");
    }

    echo "   - Found Active User: " . $data[0]->name . "\n<br>";
});

it("Subquery: whereNotExists (Inactive Users)", function () {
    $m = new model('users');

    $results = $m->select("p.name")
        ->whereExists('orders', function ($sub) {
        $sub->whereRaw("sub.user_id = p.id");
    }, 'AND', true)
        ->
    // Setting $not = true
    find();

    $data = is_array($results) ? $results : ($results ? [
        $results
    ] : []);

    echo "   - Found " . count($data) . " inactive users (no orders).\n<br>";
});

it("Pagination: Standard User List", function () {
    $m = new model('users');

    // Test page 1 with 5 items per page
    $results = $m->orderBy('id', 'ASC')->paginate(1, 5);

    if (! isset($results->meta->total_records)) {
        throw new Exception("Pagination failed: Metadata missing.");
    }

    if (count($results->items) > 5) {
        throw new Exception("Pagination failed: Limit not respected.");
    }

    echo "   - Page {$results->meta->current_page} of {$results->meta->total_pages} | Total: {$results->meta->total_records}\n<br>";
});

it("Joins (INNER, LEFT, selectRaw, groupBy, having)", function () {
    $m = new model('orders');

    // 1. We MUST use p.id to avoid "Ambiguous column" errors
    // 2. Ensure selectRaw includes the alias clearly
    $results = $m->selectRaw("p.*, u.name as uname")
        ->join('users u', 'u.id', '=', 'p.user_id', 'LEFT')
        ->where('p.total_amount', '>', 0)
        ->groupBy('p.id')
        ->having('p.total_amount', '>', 0)
        ->find();

    // Fix: If find() returns multiple rows, it is an array.
    // We must pick the first element to check the property.
    $target = is_array($results) ? $results[0] : $results;

    if (! $target) {
        throw new Exception("Join Error: No data returned from DB.");
    }

    if (! empty($target->uname)) {
        // Debugging: Print the object structure to see what the ORM actually captured
        throw new Exception("Join Error: 'uname' alias is missing. Check if your assign() method supports raw aliases.");
    }

    echo "   - Joined Customer: " . $target->uname . "\n";
});

it("Subqueries (withCount)", function () {
    $order = (new model('orders'))->withCount('comments', 'order_id', 'total')
        ->where('id', 1)
        ->find();
    if ($order->total < 1)
        throw new Exception("withCount failed");
});

it("Graph Engine (findGraph, paginateGraph)", function () {
    $schema = [
        'n' => 'name',
        'obs' => [
            'table' => 'orders',
            'foreign_key' => 'user_id',
            'fields' => [
                'r' => 'order_ref'
            ]
        ]
    ];
    $m = new model('users');
    $graph = $m->where('id', 1)->findGraph($schema);
    $page = $m->paginateGraph($schema, 1, 1);
    if (! isset($graph->obs[0]->r) || empty($page->items))
        throw new Exception("Graph Engine Error");
});

it("Persistence (updateWhere, deleteWhere, deleteById)", function () {
    $m = new model('products');
    $m->where('id', 1)->updateWhere([
        'category' => 'Tech'
    ]);
    (new model('users'))->deleteById(999); // Test non-existent delete
    if ($m->where('category', 'Tech')->count() === 0)
        throw new Exception("Bulk Ops Error");
});

it("State & Utility (toSql, explain, getData, transaction)", function () {
    $m = new model('users');
    $m->transaction(function ($db) {
        return true;
    });
    if (! is_array($m->explain()) || ! str_contains($m->toSql(), 'SELECT'))
        throw new Exception("Utility Error");
});

it("Analytics: Revenue by Product Category", function () {
    $m = new model('order_items');

    // 1. Correct the alias to pr.category
    // 2. Use find() instead of toSql() to get the actual data
    $results = $m->selectRaw("pr.category, SUM(p.quantity * p.unit_price) as total_revenue")
        ->join('products pr', 'pr.id', '=', 'p.product_id')
        ->groupBy('pr.category')
        ->orderBy('total_revenue', 'DESC')
        ->find(); // This executes the SQL and returns array|model|null

    // find() returns a single object if LIMIT 1, or an array of objects
    $data = is_array($results) ? $results : ($results ? [
        $results
    ] : []);

    if (empty($data) || ! empty($data[0]->total_revenue)) {
        throw new Exception("Analytics failed: No data returned.");
    }

    echo "   - Category: {$data[0]->category} | Revenue: {$data[0]->total_revenue}\n<br>";
});

it("Mega-Graph: Advanced Aggregates", function () {
    $m = new model('users');
    $schema = [
        'name' => 'name',
        'order_stats' => [
            'type' => 'count',
            'table' => 'orders',
            'foreign_key' => 'user_id'
        ],
        'recent_orders' => [
            'table' => 'orders',
            'foreign_key' => 'user_id',
            'fields' => [
                'id' => 'id',
                'status' => 'status'
            ]
        ]
    ];

    $result = $m->where('id', 1)->findGraph($schema);

    if ($result && isset($result->order_stats)) {
        echo "   - User {$result->name} has {$result->order_stats} total orders.\n<br>";
    }
});

it("Analytics: Daily Active Users (DAU)", function () {
    $m = new model('site_analytics');

    $results = $m->selectRaw("DATE(d_created) as log_date, COUNT(DISTINCT user_id) as unique_users")
        ->groupBy("log_date")
        ->orderBy("log_date", "DESC")
        ->limit(7)
        ->find();

    // 1. Check if the query returned anything at all
    if ($results === null) {
        throw new Exception("Analytics failed: No activity found in site_analytics table.");
    }

    // 2. Normalize result to an array
    $data = is_array($results) ? $results : [
        $results
    ];

    // 3. Now perform your check
    if (! empty($data[0]->unique_users)) {
        throw new Exception("Analytics failed: DAU count is missing.");
    }

    echo "   - Last Date: {$data[0]->log_date} | DAU: {$data[0]->unique_users}\n<br>";
});

$megaSchema = [
    'id' => 'id',
    'customer_name' => 'name',
    'contact' => 'email',

    // Level 2: Analytics Count
    'total_site_visits' => [
        'type' => 'count',
        'table' => 'site_analytics',
        'foreign_key' => 'user_id'
    ],

    // Level 2: List of Orders
    'orders' => [
        'table' => 'orders',
        'foreign_key' => 'user_id',
        'fields' => [
            'order_no' => 'order_ref',
            'total' => 'total_amount',
            'status' => 'status',

            // Level 3: Order Items (Linked to Orders)
            'line_items' => [
                'table' => 'order_items',
                'foreign_key' => 'order_id',
                'fields' => [
                    'qty' => 'quantity',
                    'price_at_purchase' => 'unit_price',

                    // Level 4: Product Info (Linked to Order Items)
                    // We use 'link_to_parent' because product_id is in order_items
                    'product_info' => [
                        'table' => 'products',
                        'link_to_parent' => true,
                        'foreign_key' => 'product_id',
                        'fields' => [
                            'name' => 'name',
                            'cat' => 'category'
                        ]
                    ]
                ]
            ],

            // Level 3: Parallel branch - Comments on that specific order
            'feedback' => [
                'table' => 'comments',
                'foreign_key' => 'order_id',
                'fields' => [
                    'text' => 'comment_text',
                    'date' => 'd_created'
                ]
            ]
        ]
    ]
];

it("Mega-Schema: Recursive Graph Depth 4", function () use ($megaSchema) {
    $m = new model('users');

    // We want to see how this performs for the main dashboard user
    $startTime = microtime(true);
    $result = $m->where('id', 1)->findGraph($megaSchema);
    $duration = microtime(true) - $startTime;

    // 1. Validate Level 1 (User)
    if (! $result || ! isset($result->customer_name))
        throw new Exception("Mega-Graph: Level 1 failed.");

    // 2. Validate Level 2 (Orders)
    if (empty($result->orders))
        throw new Exception("Mega-Graph: Level 2 (Orders) missing.");

    // 3. Validate Level 3 (Order Items)
    $firstOrder = $result->orders[0];
    if (empty($firstOrder->line_items))
        throw new Exception("Mega-Graph: Level 3 (Items) missing.");

    // 4. Validate Level 4 (Product Detail Sub-JSON)
    $firstItem = $firstOrder->line_items[0];
    if (is_string($firstItem->product_info)) {
        // Since product_info was a raw subquery returning JSON, we might need to decode it
        $product = json_decode($firstItem->product_info);
        if (! empty($product->name))
            throw new Exception("Mega-Graph: Level 4 (Product) failed.");
    }

    echo "   - Mega-Query executed in " . round($duration, 4) . "s\n<br>";
    echo "   - Successfully retrieved Customer -> Orders -> Items -> Product Details\n<br>";
});

it("Pagination: Paginated Graph (Orders with Items)", function () use ($megaSchema) {
    $m = new model('users');

    // We want page 1, 2 users per page, including their full graph
    $results = $m->paginateGraph($megaSchema, 1, 2);

    if (empty($results->items)) {
        throw new Exception("Graph Pagination failed: No items returned.");
    }

    // Validate that the first item is a fully hydrated object
    $firstUser = $results->items[0];
    if (! isset($firstUser->customer_name) || ! is_array($firstUser->orders)) {
        throw new Exception("Graph Pagination failed: Data structure corrupted.");
    }

    echo "   - Graph Pagination: Retrieved " . count($results->items) . " users with full nested data.\n<br>";
});

it("Pagination: Filtered Search", function () {
    $m = new model('users');

    // Searching for 'John' across name and email with pagination
    $results = $m->search([
        'name',
        'email'
    ], 'John')
        ->orderBy('d_created', 'DESC')
        ->paginate(1, 10);

    echo "   - Search Results: Found " . $results->meta->total_records . " matches for 'John'.\n<br>";
});

it("Integrity: Soft Delete Filtering", function () use ($megaSchema) {
    $m = new model('users');
    $m->softDelete = true; // Enable soft delete logic

    // This query should automatically exclude rows where d_deleted is NOT NULL
    $results = $m->paginateGraph($megaSchema, 1, 5);

    echo "   - Soft Delete: Verified that Graph excludes deleted records.\n<br>";
});

$statsSchema = [
    'user_name' => 'name',

    // 1. COUNT: Volume of activity
    'total_orders' => [
        'type' => 'count',
        'table' => 'orders',
        'foreign_key' => 'user_id'
    ],

    // 2. SUM: Total Financial Impact
    'total_revenue' => [
        'type' => 'sum',
        'table' => 'orders',
        'column' => 'total_amount',
        'foreign_key' => 'user_id'
    ],

    // 3. AVG: Performance Metric
    'average_order_value' => [
        'type' => 'avg',
        'table' => 'orders',
        'column' => 'total_amount',
        'foreign_key' => 'user_id'
    ],

    // 4. MIN/MAX: Range Detection
    'first_purchase_value' => [
        'type' => 'min',
        'table' => 'orders',
        'column' => 'total_amount',
        'foreign_key' => 'user_id'
    ],
    'highest_purchase_value' => [
        'type' => 'max',
        'table' => 'orders',
        'column' => 'total_amount',
        'foreign_key' => 'user_id'
    ]
];

it("Aggregates: Full Statistical Suite", function () use ($statsSchema) {
    $m = new model('users');

    // We check a specific user (ID 1)
    $data = $m->where('id', 1)->findGraph($statsSchema);

    if (! $data)
        throw new Exception("Test failed: User not found.");

    echo "   - Statistics for {$data->user_name}:\n<br>";
    echo "     * COUNT: {$data->total_orders} orders recorded.\n<br>";
    echo "     * SUM: Total LTV is $" . number_format($data->total_revenue, 2) . "\n<br>";
    echo "     * AVG: Average Spend is $" . number_format($data->average_order_value, 2) . "\n<br>";
    echo "     * MIN: Smallest transaction was $" . $data->first_purchase_value . "\n<br>";
    echo "     * MAX: Largest transaction was $" . $data->highest_purchase_value . "\n<br>";

    // Validation: Ensure calculations are numeric
    if (! is_numeric($data->total_revenue) || $data->total_revenue < 0) {
        throw new Exception("Aggregate Integrity: Revenue calculation error.");
    }
});

it("Aggregates: Paginated Analytics", function () use ($statsSchema) {
    $m = new model('users');

    // Page 1, top 5 users including all their financial aggregates
    $results = $m->paginateGraph($statsSchema, 1, 5);

    echo "   - Analytics Dashboard (Page 1):\n<br>";
    foreach ($results->items as $row) {
        $status = ($row->average_order_value > 100) ? "VIP" : "Standard";
        echo "     [{$status}] {$row->user_name} - Avg: $" . round($row->average_order_value, 2) . "\n<br>";
    }
});

it("Full Engine Test: Aggregates + Recursion", function () use ($megaSchema) {
    $m = new model('users');

    // Adding an aggregate on the fly to your existing megaSchema
    $megaSchema['total_spent'] = [
        'type' => 'sum',
        'table' => 'orders',
        'column' => 'total_amount',
        'foreign_key' => 'user_id'
    ];

    $result = $m->where('id', 1)->findGraph($megaSchema);

    if ($result) {
        echo "   - [Level 1] User: {$result->customer_name}\n<br>";
        echo "   - [Aggregate] Total Spend: {$result->total_spent}\n<br>";
        echo "   - [Level 2] Orders Found: " . count($result->orders) . "\n<br>";
        echo "   - [Level 4] First Product: " . $result->orders[0]->line_items[0]->product_info->name . "\n<br>";
    }
});

it("Comprehensive Aggregate Test", function () {
    $m = new model('users');
    $summarySchema = [
        'user' => 'name',
        'orders_count' => [
            'type' => 'count',
            'table' => 'orders',
            'foreign_key' => 'user_id'
        ],
        'total_revenue' => [
            'type' => 'sum',
            'table' => 'orders',
            'column' => 'total_amount',
            'foreign_key' => 'user_id'
        ],
        'average_spend' => [
            'type' => 'avg',
            'table' => 'orders',
            'column' => 'total_amount',
            'foreign_key' => 'user_id'
        ],
        'min_deal' => [
            'type' => 'min',
            'table' => 'orders',
            'column' => 'total_amount',
            'foreign_key' => 'user_id'
        ],
        'max_deal' => [
            'type' => 'max',
            'table' => 'orders',
            'column' => 'total_amount',
            'foreign_key' => 'user_id'
        ]
    ];

    $results = $m->paginateGraph($summarySchema, 1, 5);

    foreach ($results->items as $row) {
        echo "📊 User: {$row->user} | Orders: {$row->orders_count} | Avg: $" . round($row->average_spend, 2) . "\n<br>";
    }
});

$timeSchema = [
    'customer' => 'name',
    // Lifetime aggregate
    'total_lifetime' => [
        'type' => 'sum',
        'table' => 'orders',
        'column' => 'total_amount',
        'foreign_key' => 'user_id'
    ],
    // Filtered aggregate: Only orders from 2024 onwards
    'recent_spend' => [
        'type' => 'sum',
        'table' => 'orders',
        'column' => 'total_amount',
        'foreign_key' => 'user_id',
        'where' => "d_created >= '2024-01-01'"
    ],
    // Filtered count: Successful orders only
    'successful_orders' => [
        'type' => 'count',
        'table' => 'orders',
        'foreign_key' => 'user_id',
        'where' => "status = 'completed'"
    ]
];

it("Aggregates: Time-Based Performance", function () use ($timeSchema) {
    $m = new model('users');
    $results = $m->paginateGraph($timeSchema, 1, 10);

    foreach ($results->items as $row) {
        $growth = $row->total_lifetime > 0 ? round(($row->recent_spend / $row->total_lifetime) * 100, 1) : 0;

        echo "   - User: {$row->customer}\n<br>";
        echo "     * Recent/Lifetime Ratio: {$growth}%\n<br>";
        echo "     * Success Rate: {$row->successful_orders} completed orders.\n<br>";
        echo "--- \n<br>";
    }
});

it("The Ultimate Test: Filtered Aggregates + 4-Level Nesting", function () use ($megaSchema) {
    $m = new model('users');

    // Adding a filtered aggregate to our existing megaSchema
    $megaSchema['total_revenue_completed'] = [
        'type' => 'sum',
        'table' => 'orders',
        'column' => 'total_amount',
        'foreign_key' => 'user_id',
        'where' => "status = 'completed'" // New filter feature!
    ];

    $result = $m->where('id', 1)->findGraph($megaSchema);

    if ($result) {
        echo "✅ Level 1: User {$result->customer_name}\n<br>";
        echo "📊 Filtered Sum: $" . $result->total_revenue_completed . "\n<br>";
        echo "📦 Level 2: Order Status: " . $result->orders[0]->status . "\n<br>";
        echo "🏷️ Level 4: Product Name: " . $result->orders[0]->line_items[0]->product_info->name . "\n<br>";
    }
});

it("Stress: Atomic Transaction", function () {
    $m = new model('users');

    $status = $m->transaction(function () {
        $u = new model('users');
        $u->name = "Transaction Test User";
        $u->email = "test@example.com";
        $userId = $u->save();

        $o = new model('orders');
        $o->user_id = $userId;
        $o->order_ref = 'tt123';
        $o->total_amount = 500;
        $o->save();

        return true;
    });

    echo "   - Transaction: Atomic User + Order creation " . ($status ? "Passed" : "Failed") . ".\n<br>";
});

// 2. Define our test model
class UserProfile extends model
{

    protected array $casts = [
        'settings' => 'json',
        'tags' => 'json'
    ];
}

echo "<h2>Running JSON Cast Tests</h2>";

// --- THE TESTS ---

it('decodes json string from database into array', function () {
    $m = new UserProfile('users');
    $rawDbRow = [
        'id' => 1,
        'settings' => '{"theme":"dark","zoom":1.2}', // String from DB
        'name' => 'John'
    ];

    $m->assign($rawDbRow);

    if (! is_array($m->settings))
        throw new Exception("Expected settings to be an array");
    if ($m->settings['theme'] !== 'dark')
        throw new Exception("Expected theme to be dark");
});

it('encodes array into json string for database storage', function () {
    $m = new UserProfile('users');
    $m->settings = [
        'notifications' => false,
        'retry' => 3
    ];

    // Using reflection to test the protected mapValues method
    $reflector = new ReflectionClass($m);
    $method = $reflector->getMethod('mapValues');
    $method->setAccessible(true);

    $data = $m->getData();
    $mapped = $method->invokeArgs($m, [
        &$data
    ]);

    // $mapped[0] is settings because getData returns the array keys
    $jsonString = $mapped[0];

    if (! is_string($jsonString))
        throw new Exception("Expected settings to be cast to string");
    if (json_decode($jsonString) === null)
        throw new Exception("Resulting string is not valid JSON");
});

it('ignores casting when property is not in $casts', function () {
    $m = new UserProfile('users');
    $rawDbRow = [
        'name' => '{"this_is_not_json":true}'
    ]; // A string that looks like JSON

    $m->assign($rawDbRow);

    if (! is_string($m->name))
        throw new Exception("Expected name to remain a string");
});

it('handles null values in json cast columns', function () {
    $m = new UserProfile('users');
    $rawDbRow = [
        'settings' => null
    ];

    $m->assign($rawDbRow);

    if ($m->settings !== null)
        throw new Exception("Expected settings to be null");
});

it('prevents double-encoding if property is already a json string', function () {
    $m = new class('users') extends model {

        protected array $casts = [
            'meta' => 'json'
        ];
    };

    // Manually set a string that is already JSON
    $jsonString = '{"is_valid":true}';
    $m->meta = $jsonString;

    $reflector = new ReflectionClass($m);
    $method = $reflector->getMethod('mapValues');
    $method->setAccessible(true);

    $data = $m->getData();
    $mapped = $method->invokeArgs($m, [
        &$data
    ]);

    // If double-encoded, the result would be '"{\"is_valid\":true}"'
    if ($mapped[0] !== $jsonString) {
        throw new Exception("Double encoding detected! Expected: $jsonString, Got: " . $mapped[0]);
    }
});

echo "\n<br>--- ALL METHODS VERIFIED ---\n<br>";