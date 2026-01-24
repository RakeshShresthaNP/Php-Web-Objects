<?php
declare(strict_types = 1);

require_once 'db.php';
require_once '../myapp/app/models/model.php';

echo "--- ORM METHOD COVERAGE TEST ---\n<br>";

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

echo "\n<br>--- ALL METHODS VERIFIED ---\n<br>";