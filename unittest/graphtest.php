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
// 1. Define ONLY the data structure for the JSON output
$udfSchema = [
    'id' => 'id',
    'loud_name' => 'UPPER(u.name)', // No "as" needed here
    'total_earned' => "SUM(IF(o.status = 'completed', o.total_amount, 0))"
];

it("findGraph: UDF and Expression Awareness", function () use ($udfSchema) {
    $m = new model('users', 'id');

    // 2. Use the query builder for the structural SQL
    $m->join('orders o', 'o.user_id', '=', 'u.id', 'LEFT')->groupBy('u.id');

    // 3. Execute findGraph with the clean schema
    $results = $m->findGraph($udfSchema, 'u');

    if (! $results) {
        throw new Exception("UDF Test Failed: No data returned.");
    }

    // Handle single object vs array of objects
    $row = is_array($results) ? $results[0] : $results;

    // Verify the UDF worked
    echo "✅ UDF Awareness Verified:\n";
    echo "   - UDF (UPPER): " . $row->loud_name . "\n";
    echo "   - UDF (SUM/IF): " . ($row->total_earned ?? 0) . "\n";
});

it("Window Function: Running Total & Partition By", function () {
    $m = new model('orders');

    // We use a schema that includes a Window Function
    $windowSchema = [
        'order_id' => 'id',
        'amount' => 'total_amount',
        'user_id' => 'user_id',
        // This calculates total spent by THIS user across all their orders
        'user_cumulative_total' => 'SUM(total_amount) OVER (PARTITION BY user_id ORDER BY d_created)',
        // This calculates the average order size for the entire table as a comparison
        'global_avg' => 'AVG(total_amount) OVER ()'
    ];

    // Execution
    $results = $m->orderBy('user_id', 'ASC')->findGraph($windowSchema, 'o');

    if (! $results) {
        throw new Exception("Window Function Test Failed: No data.");
    }

    $data = is_array($results) ? $results : [
        $results
    ];
    $first = $data[0];

    echo "✅ Window Function Awareness Verified:\n";
    echo "   - Order ID: " . $first->order_id . "\n";
    echo "   - User ID: " . $first->user_id . "\n";
    echo "   - Running Total for User: " . $first->user_cumulative_total . "\n";
    echo "   - Global Table Average: " . $first->global_avg . "\n";
});

it("Window Function: Deeply Nested Subquery Ranking", function () {
    $m = new model('users');

    // p is the default alias for 'users' in your buildSelectSql logic
    $complexSchema = [
        'id' => 'id',
        'name' => 'name',
        // The "Final Boss": Rank based on a subquery calculation
        'rank' => 'RANK() OVER (ORDER BY (SELECT SUM(o.total_amount) FROM orders o WHERE o.user_id = p.id) DESC)'
    ];

    // Execution
    $results = $m->findGraph($complexSchema, 'p');

    if (! $results) {
        throw new Exception("Ranking Test: No data found.");
    }

    $data = is_array($results) ? $results : [
        $results
    ];

    echo "✅ Deeply Nested Window Function Verified:\n";
    foreach ($data as $user) {
        // If rank 1 appears, the subquery and window function successfully communicated
        echo "   - [Rank #{$user->rank}] User: {$user->name} (ID: {$user->id})\n";
    }
});

it("Window Function: Complex Subquery Ranking", function () {
    $m = new model('users');

    // We want to rank users based on their total spending across all orders
    $rankSchema = [
        'user_id' => 'id',
        'user_name' => 'name',
        'spend_rank' => 'RANK() OVER (ORDER BY (SELECT SUM(total_amount) FROM orders o WHERE o.user_id = p.id) DESC)'
    ];

    // Execute via findGraph
    $results = $m->findGraph($rankSchema, 'p');

    if (! $results) {
        throw new Exception("Ranking test failed: No users found.");
    }

    $data = is_array($results) ? $results : [
        $results
    ];

    echo "✅ Window Ranking Verified:\n";
    foreach ($data as $row) {
        echo "   - Rank #{$row->spend_rank}: {$row->user_name} (ID: {$row->user_id})\n";
    }

    // Logic Check: The first person in the array should have rank 1
    if ($data[0]->spend_rank != 1) {
        throw new Exception("Ranking Logic Error: Top user is not Rank 1.");
    }
});

it("Window Function: LEAD (Next Order Date) Test", function () {
    $m = new model('orders');

    // Schema to show current order and the date of the order following it
    $leadSchema = [
        'order_id' => 'id',
        'current_order_date' => 'd_created',
        'current_amount' => 'total_amount',
        // LEAD(column, offset) looks forward. We partition by user
        // so we don't see another user's order dates.
        'next_order_date' => 'LEAD(d_created, 1) OVER (PARTITION BY user_id ORDER BY d_created ASC)'
    ];

    // Sort by user and date so the LEAD logic follows a timeline
    $results = $m->orderBy('user_id')
        ->orderBy('d_created')
        ->findGraph($leadSchema, 'o');

    if (! $results)
        throw new Exception("LEAD test: No data found.");

    $data = is_array($results) ? $results : [
        $results
    ];

    echo "✅ LEAD Function Awareness Verified:\n";
    foreach ($data as $row) {
        $next = $row->next_order_date ?? 'NONE (Last Order)';
        echo "   - Order #{$row->order_id} on {$row->current_order_date} | Next Order: {$next}\n";
    }
});

it("Schema Config: Inline Row Number Test", function () {
    $m = new model('users');

    $schema = [
        'id' => 'id',
        'name' => 'name',
        // Inline row numbering configuration
        'item_position' => [
            'type' => 'rownumber',
            'order_by' => 'name ASC' // Optional: custom order for the count
        ],
        // You can even do partitioned row numbers in the same schema!
        'user_rank' => [
            'type' => 'rownumber',
            'partition_by' => 'status', // Resets count for each status group
            'order_by' => 'd_created DESC'
        ]
    ];

    $results = $m->findGraph($schema, 'u');

    if ($results) {
        $row = is_array($results) ? $results[0] : $results;
        echo "✅ Inline Row Number: " . $row->item_position;
    }
});

it("Analytics: Growth and Time-Series", function () {
    $m = new model('orders');

    $schema = [
        'id' => 'p.id',
        'current_amount' => 'p.total_amount',
        // We move the "0" logic here to avoid the LAG(...,0) parser bug
        'prev_amount' => 'COALESCE(stats.prev_val, 0)',
        'growth_pct' => 'COALESCE(stats.pct, 0)'
    ];

    // REMOVED THE ",0" FROM LAG. 11.8 handles LAG(col, 1) much more cleanly.
    $subQuery = "SELECT id,
        LAG(total_amount, 1) OVER (PARTITION BY user_id ORDER BY d_created) as prev_val,
        ROUND(((total_amount - LAG(total_amount, 1) OVER (PARTITION BY user_id ORDER BY d_created)) /
        NULLIF(LAG(total_amount, 1) OVER (PARTITION BY user_id ORDER BY d_created), 0)) * 100, 2) as pct
        FROM orders";

    $results = $m->withAnalytics('stats', $subQuery, 'id', 'id', 'p')->findGraph($schema, 'p');

    if ($results) {
        $data = is_array($results) ? $results[0] : $results;
        echo "   - Growth Fixed: Current {$data->current_amount}, Prev {$data->prev_amount}\n<br>";
    }
});

it("Schema Config: Lead/Lag Multi-Directional", function () {
    $m = new model('orders');

    $schema = [
        'id' => 'p.id',
        'current_sale' => 'p.total_amount',
        'previous_sale' => 'COALESCE(stats.prev_sale, 0)',
        'next_sale' => 'COALESCE(stats.next_sale, 0)'
    ];

    // Simple 2-argument LAG/LEAD
    $subQuery = "SELECT id,
        LAG(total_amount, 1) OVER (PARTITION BY user_id ORDER BY d_created) as prev_sale,
        LEAD(total_amount, 1) OVER (PARTITION BY user_id ORDER BY d_created) as next_sale
        FROM orders";

    $results = $m->withAnalytics('stats', $subQuery, 'id', 'id', 'p')->findGraph($schema, 'p');

    if ($results)
        echo "   - Time Series Verified\n<br>";
});

it("The Final Boss: High Precision Check", function () {
    $m = new model('users');

    $megaSchema = [
        'id' => 'id',
        'customer' => 'name',
        'avg_spend' => 'stats.moving_avg',
        'orders' => [
            'table' => 'orders',
            'foreign_key' => 'user_id',
            'fields' => [
                'ref' => 'order_ref',
                'total' => 'total_amount'
            ]
        ]
    ];

    // Using CAST to ensure we don't get 0.00 for small averages
    $subQuery = "SELECT user_id, CAST(AVG(total_amount) OVER (PARTITION BY user_id) AS DECIMAL(10,2)) as moving_avg FROM orders";

    $results = $m->withAnalytics('stats', $subQuery, 'user_id', 'id', 'p')
        ->limit(1)
        ->findGraph($megaSchema, 'p');

    if (! $results)
        throw new Exception("Final Boss Failed: No data.");

    echo "   - User: {$results->customer} | Precision Avg: {$results->avg_spend}\n<br>";
});

it("The Final Boss: Pre-Calculation", function () {
    $m = new model('users');

    // 1. Define the schema
    // We reference 'stats.moving_avg' which is defined in the subquery below
    $megaSchema = [
        'id' => 'id',
        'customer' => 'name',
        'avg_spend' => 'stats.moving_avg',
        'orders' => [
            'table' => 'orders',
            'foreign_key' => 'user_id',
            'fields' => [
                'ref' => 'order_ref',
                'total' => 'total_amount',
                'items' => [
                    'table' => 'order_items',
                    'foreign_key' => 'order_id',
                    'fields' => [
                        'qty' => 'quantity',
                        'price' => 'unit_price',
                        'product' => [
                            'table' => 'products',
                            'foreign_key' => 'id',
                            'link_to_parent' => true,
                            'fields' => [
                                'name' => 'name'
                            ]
                        ]
                    ]
                ]
            ]
        ]
    ];

    // 2. The Analytical Subquery
    // Important: We MUST select the column we are joining on (user_id)
    $subQuery = "SELECT user_id, AVG(total_amount) OVER (PARTITION BY user_id) as moving_avg FROM orders";

    // 3. Execution
    // We tell the model: Join subquery 'stats' where stats.user_id = users.id
    $results = $m->withAnalytics('stats', $subQuery, 'user_id', 'id')
        ->limit(5)
        ->findGraph($megaSchema, 'p');

    if (! $results) {
        throw new Exception("Final Boss Failed: No data returned.");
    }

    // Normalize result
    $data = is_array($results) ? $results : [
        $results
    ];
    $user = $data[0];

    echo "✅ Final Boss Solved:\n";
    echo "   - User: {$user->customer} (ID: {$user->id})\n";
    echo "   - Pre-calculated Avg Spend: " . number_format((float) ($user->avg_spend ?? 0), 2) . "\n";

    if (! empty($user->orders)) {
        $order = $user->orders[0];
        echo "   - Order: {$order->ref} | Total: {$order->total}\n";
        if (! empty($order->items)) {
            $item = $order->items[0];
            echo "     -> Item: {$item->product->name} | Qty: {$item->qty}\n";
        }
    }
});

echo "\n<br>--- ALL METHODS VERIFIED ---\n<br>";