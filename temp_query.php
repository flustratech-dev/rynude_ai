<?php
$user = App\Models\User::where('email', 'mryanrizki11@gmail.com')->first();
if ($user) {
    $conv = $user->conversations()->latest()->first();
    if ($conv) {
        echo "Conversation ID: " . $conv->id . "\n";
        $messages = $conv->messages()->orderBy('created_at', 'asc')->get();
        foreach ($messages as $msg) {
            echo "--- " . $msg->role . " ---\n";
            echo substr($msg->content, 0, 500) . "\n";
            echo "\n\n";
        }
    } else {
        echo "No conversations found for user.\n";
    }
} else {
    echo "User not found.\n";
}
