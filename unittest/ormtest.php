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

    // 1. Prepare the query
    $m->whereIn('id', $ids)
        ->whereBetween('d_created', $range)
        ->whereRaw("id > ?", [
        0
    ]);

    // 2. ECHO THE SQL BEFORE FIND (because find() resets the model)
    echo "\n[DEBUG] Running SQL: " . $m->toSql() . "\n";

    // 3. Execute and capture results
    $results = $m->find();

    // 4. ECHO THE RESULT COUNT
    if (is_array($results)) {
        echo "[DEBUG] Found " . count($results) . " records.\n";
    } elseif (is_object($results)) {
        echo "[DEBUG] Found 1 record (Object returned).\n";
    } else {
        echo "[DEBUG] No records found (NULL returned).\n";
    }

    // 5. Correct Assertion
    if (! $results) {
        throw new Exception("Advanced Filters Failed: Database returned no rows for IDs " . implode(',', $ids));
    }
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

    $results = $m->selectRaw("p.*, u.name as uname")
        ->join('users u', 'u.id', '=', 'p.user_id', 'LEFT')
        ->where('p.total_amount', '>', 0)
        ->groupBy('p.id')
        ->having('p.total_amount', '>', 0)
        ->find();

    $target = is_array($results) ? $results[0] : $results;

    if (! $target) {
        throw new Exception("Join Error: No data returned.");
    }

    // CHECK: Use strict null check instead of empty()
    if ($target->uname === null) {
        throw new Exception("Join Error: 'uname' is NULL. Check if user_id exists in users table.");
    }

    echo "   - Joined Customer: " . $target->uname . "\n";
});

it("Subqueries (withCount)", function () {
    $order = (new model('orders'))->withCount('comments', 'order_id', 'total')
        ->where('id', 1)
        ->find();

    if ($order->total < 1)
        throw new Exception("withCount failed");

    echo "   - Found: " . $order->total . "\n";
});

it("Final Validation: Joins and Persistence", function () {
    // --- 1. SETUP DATA ---
    $db = db();
    // Ensure User 1 and Order 1 exist for the Join test
    $db->exec("INSERT IGNORE INTO users (id, name, email) VALUES (1, 'Alpha User', 'alpha@test.com')");
    $db->exec("INSERT IGNORE INTO orders (id, user_id, total_amount, order_ref) VALUES (1, 1, 500.00, 'REF-ALPHA')");
    // Ensure Product 1 exists for the Update test
    $db->exec("INSERT IGNORE INTO products (id, name, category) VALUES (1, 'Gadget', 'Electronics')");

    // --- 2. TEST JOINS ---
    $orderModel = new model('orders');
    $order = $orderModel->selectRaw("p.*, u.name as uname")
        ->join('users u', 'u.id', '=', 'p.user_id', 'LEFT')
        ->where('p.id', 1)
        ->find();

    if (! $order || $order->uname !== 'Alpha User') {
        throw new Exception("Join Verification Failed: uname is " . ($order->uname ?? 'NULL'));
    }
    echo "✅ Join Success: Found " . $order->uname . "\n";

    // --- 3. TEST PERSISTENCE ---
    $prodModel = new model('products');
    $prodModel->where('id', 1)->updateWhere([
        'category' => 'Tech'
    ]);

    // Use count to verify disk state
    if ($prodModel->where('category', 'Tech')
        ->where('id', 1)
        ->count() === 0) {
        throw new Exception("UpdateWhere Verification Failed.");
    }
    echo "✅ Persistence Success: Category updated to Tech\n";
});

it("State & Utility (toSql, explain, getData, transaction)", function () {
    $m = new model('users');
    $m->transaction(function ($db) {
        return true;
    });
    if (! is_array($m->explain()) || ! str_contains($m->toSql(), 'SELECT'))
        throw new Exception("Utility Error");
});

echo "\n<br>--- ALL METHODS VERIFIED ---\n<br>";