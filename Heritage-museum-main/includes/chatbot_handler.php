<?php
session_start();
require_once 'db.php';

// Get user ID from session
$userId = null;
if (isset($_SESSION['user_id'])) {
    $userId = $_SESSION['user_id'];
} elseif (isset($_SESSION['user'])) {
    $stmt = $conn->prepare("SELECT id FROM users WHERE username = ?");
    $stmt->bind_param("s", $_SESSION['user']);
    $stmt->execute();
    $result = $stmt->get_result();
    if ($user = $result->fetch_assoc()) {
        $userId = $user['id'];
        $_SESSION['user_id'] = $userId;
    }
}

// Handle incoming POST request
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['message'])) {
    $message = $_POST['message'];
    
    if ($userId) {
        $response = handleChatbotMessage($message, $userId);
        echo $response;
    } else {
        echo "Please log in to use the booking system.";
    }
    exit;
}

function handleChatbotMessage($message, $userId) {
    global $conn;
    
    // Initialize booking session if not exists
    if (!isset($_SESSION['booking_state'])) {
        $_SESSION['booking_state'] = 'initial';
    }

    $response = '';
    $state = $_SESSION['booking_state'];

    switch ($state) {
        case 'initial':
            if (strtolower($message) === 'book') {
                $_SESSION['booking_state'] = 'select_show';
                $response = "Please select a show by entering its number:\n\n";
                
                // Fetch shows from database
                $shows = getShowsFromDatabase($conn);
                foreach ($shows as $id => $show) {
                    $response .= "{$id}. {$show['name']} - ₹{$show['price']}\n";
                    $response .= "   {$show['description']}\n\n";
                }
            } else {
                $response = "Welcome to Museum Booking System!\nAvailable shows:\n\n";
                
                // Fetch shows from database for welcome message
                $shows = getShowsFromDatabase($conn);
                foreach ($shows as $show) {
                    $response .= "{$show['name']} - ₹{$show['price']}\n";
                    $response .= "{$show['description']}\n\n";
                }
                $response .= "Type 'book' to start booking tickets.";
            }
            break;

        case 'select_show':
            $shows = getShowsFromDatabase($conn);

            if (isset($shows[$message])) {
                $show = $shows[$message];
                $_SESSION['show_id'] = $message;
                $_SESSION['show_name'] = $show['name'];
                $_SESSION['show_price'] = $show['price'];
                $_SESSION['booking_state'] = 'num_tickets';
                $response = "You selected: {$show['name']}\n";
                $response .= "Price per ticket: ₹{$show['price']}\n\n";
                $response .= "How many tickets would you like to book?";
            } else {
                $response = "Please select a valid show number.";
            }
            break;

        case 'num_tickets':
            if (is_numeric($message) && $message > 0) {
                $_SESSION['num_tickets'] = (int)$message;
                $_SESSION['booking_state'] = 'visitor_name';
                $response = "Please enter the visitor's name:";
            } else {
                $response = "Please enter a valid number of tickets.";
            }
            break;

        case 'visitor_name':
            if (!empty($message)) {
                $_SESSION['visitor_name'] = $message;
                $_SESSION['booking_state'] = 'show_time';
                $response = "Please select a time slot by entering its number:\n\n";
                $response .= "1. 10:00 AM\n";
                $response .= "2. 12:00 PM\n";
                $response .= "3. 02:00 PM\n";
                $response .= "4. 04:00 PM\n";
                $response .= "5. 06:00 PM\n";

                $_SESSION['time_slots'] = [
                    1 => '10:00 AM',
                    2 => '12:00 PM',
                    3 => '02:00 PM',
                    4 => '04:00 PM',
                    5 => '06:00 PM'
                ];
            } else {
                $response = "Please enter a valid name.";
            }
            break;

        case 'show_time':
            if (isset($_SESSION['time_slots'][$message])) {
                $_SESSION['show_time'] = $_SESSION['time_slots'][$message];
                $_SESSION['booking_state'] = 'mobile_number';
                $response = "Please enter your mobile number:";
            } else {
                $response = "Please select a valid time slot number (1-5).";
            }
            break;

        case 'mobile_number':
            if (preg_match("/^[0-9]{10}$/", $message)) {
                $_SESSION['mobile_number'] = $message;
                $_SESSION['booking_state'] = 'working_address';
                $response = "Please enter your working address:";
            } else {
                $response = "Please enter a valid 10-digit mobile number.";
            }
            break;

        case 'working_address':
            if (!empty($message)) {
                $_SESSION['working_address'] = $message;

                $_SESSION['total_amount'] = $_SESSION['show_price'] * $_SESSION['num_tickets'];

                $response = "Great! Here's your booking summary:\n\n";
                $response .= "Show: {$_SESSION['show_name']}\n";
                $response .= "Number of Tickets: {$_SESSION['num_tickets']}\n";
                $response .= "Price per Ticket: ₹{$_SESSION['show_price']}\n";
                $response .= "Total Amount: ₹{$_SESSION['total_amount']}\n";
                $response .= "Visitor Name: {$_SESSION['visitor_name']}\n";
                $response .= "Show Time: {$_SESSION['show_time']}\n";
                $response .= "Mobile: {$_SESSION['mobile_number']}\n";
                $response .= "Working Address: {$_SESSION['working_address']}\n\n";

                $response .= "[[PAYMENT_BUTTON:" . $_SESSION['total_amount'] . "]]";

                $_SESSION['booking_state'] = 'payment_pending';
            } else {
                $response = "Please enter a valid working address.";
            }
            break;

        case 'payment_pending':
            if (strtolower($message) === 'book') {
                $_SESSION['booking_state'] = 'initial';
                return handleChatbotMessage('book', $userId);
            } else {
                $response = "Please click the 'Proceed to Payment' button above to complete your booking.\n";
                $response .= "Or type 'book' to start a new booking.";
            }
            break;

        default:
            $_SESSION['booking_state'] = 'initial';
            $response = "Welcome to Museum Booking System!\nType 'book' to start booking tickets.";
    }

    return $response;
}

// Function to fetch shows from DB
function getShowsFromDatabase($conn) {
    $shows = array();
    $query = "SELECT id, name, price, description FROM shows ORDER BY id";
    $result = $conn->query($query);
    
    if ($result) {
        while ($row = $result->fetch_assoc()) {
            $shows[$row['id']] = [
                'name' => $row['name'],
                'price' => $row['price'],
                'description' => $row['description']
            ];
        }
    }
    return $shows;
}
?>
