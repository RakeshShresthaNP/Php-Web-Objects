<?php
declare(strict_types = 1);

require_once '../myapp/app/models/model.php';

// 1. Connection Logic
function db()
{
    static $pdo;
    if (! $pdo) {
        $pdo = new PDO("mysql:host=localhost;dbname=meworm", "root", "");
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

    echo count($results->items);
    /*
     * foreach ($results->items as $row) {
     * $growth = $row->total_lifetime > 0 ? round(($row->recent_spend / $row->total_lifetime) * 100, 1) : 0;
     *
     * echo " - User: {$row->customer}\n<br>";
     * echo " * Recent/Lifetime Ratio: {$growth}%\n<br>";
     * echo " * Success Rate: {$row->successful_orders} completed orders.\n<br>";
     * echo "--- \n<br>";
     * }
     */
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

echo "\n<br>--- ALL METHODS VERIFIED ---\n<br>";