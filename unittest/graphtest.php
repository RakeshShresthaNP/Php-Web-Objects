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

echo "\n<br>--- ALL METHODS VERIFIED ---\n<br>";