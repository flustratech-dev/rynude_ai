<?php
$user = App\Models\User::where('email', 'mryanrizki11@gmail.com')->first();
if ($user) {
    $conv = $user->conversations()->latest()->first();
    if ($conv) {
        $msg = $conv->messages()->where('role', 'assistant')->latest()->first();
        if ($msg) {
            echo $msg->content;
        }
    }
}
