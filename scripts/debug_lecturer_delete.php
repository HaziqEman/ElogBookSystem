<?php
require __DIR__ . '/../vendor/autoload.php';
$app = require __DIR__ . '/../bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

use App\Models\Lecturer;
use App\Models\Message;
use App\Models\Conversation;
use Illuminate\Support\Facades\Auth;

$lecturer = Lecturer::find(23);
$message = Message::find(25);
$conversation = Conversation::find(4);

echo "LECTURER RECORD:\n";
var_export($lecturer);

echo "\nMESSAGE RECORD:\n";
var_export($message);

echo "\nCONVERSATION RECORD:\n";
var_export($conversation);

echo "\n--- Condition checks ---\n";
if (! $lecturer) {
    echo "NO lecturer found\n";
    exit(1);
}

$student = Auth::guard('student')->user();
$lectFromGuard = Auth::guard('lecturer')->user();

var_export(['student_guard' => $student ? $student->student_id : null, 'lecturer_guard' => $lectFromGuard ? $lectFromGuard->lecturer_id : null]);
echo "\n";

$matchesConversation = $message && $conversation && (int) $message->conversation_id === (int) $conversation->conversation_id;
$conversationLecturerMatches = $conversation && (int) $conversation->lecturer_id === (int) $lecturer->lecturer_id;
$senderTypeMatches = $message && $message->sender_type === get_class($lecturer);
$senderIdMatches = $message && (int) $message->sender_id === (int) $lecturer->lecturer_id;

var_export(['message_belongs_to_conversation' => $matchesConversation]);
var_export(['conversation_belongs_to_lecturer' => $conversationLecturerMatches]);
var_export(['sender_type' => $message?->sender_type, 'get_class' => get_class($lecturer), 'sender_type_matches' => $senderTypeMatches]);
var_export(['sender_id' => $message?->sender_id, 'lecturer_id' => $lecturer->lecturer_id, 'sender_id_matches' => $senderIdMatches]);

echo "\n";

if ($message && $message->sender_type === 'App\\Models\\Lecturer') {
    echo "Exact sender_type string matches App\\Models\\Lecturer\n";
} else {
    echo "sender_type is NOT App\\Models\\Lecturer\n";
}

if ($message && is_string($message->sender_id)) {
    echo "sender_id type is string\n";
} elseif ($message && is_int($message->sender_id)) {
    echo "sender_id type is int\n";
} else {
    echo "sender_id type is ".gettype($message?->sender_id)."\n";
}
