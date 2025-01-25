import Echo from 'laravel-echo';
import Pusher from 'pusher-js';

const PusherTest = () => {
    window.Pusher = Pusher;

    const echo = new Echo({
        broadcaster: 'pusher',
        key: process.env.REACT_APP_PUSHER_KEY,  // Use environment variable
        cluster: process.env.REACT_APP_PUSHER_CLUSTER,
        forceTLS: true,
    });

    // Listen for new orders from Laravel
    echo.channel('orders')
        .listen('.new-order', (event) => {
            console.log("New Order Received:", event.order);
            alert(`New Order: ${JSON.stringify(event.order)}`);
        });

    // Send order to Laravel API, not directly via Pusher!
    function sendOrderToAPI(order) {
        fetch('/api/save-order', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
            },
            body: JSON.stringify(order),
        })
        .then(response => response.json())
        .then(result => console.log("Order saved:", result))
        .catch(error => console.error("Error:", error));
    }

    return (
        <button onClick={(e) => {
            e.preventDefault();
            sendOrderToAPI({
                item_id: 2,
                no: 15,
                order_status: 1,
                establishment_id: 1
            });
        }}>
            Send Order
        </button>
    );
}

export default PusherTest;