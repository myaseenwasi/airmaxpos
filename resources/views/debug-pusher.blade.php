<!DOCTYPE html>
<html>
<head>
    <title>Pusher Debug</title>
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <script src="https://js.pusher.com/8.2.0/pusher.min.js"></script>
    <script>
        // Enable pusher logging - don't include this in production
        Pusher.logToConsole = true;

        @if($config['pusher_app_cluster'])
            console.log('Initializing Pusher with cluster:', '{{ $config['pusher_app_cluster'] }}');
            const pusher = new Pusher('{{ $config['pusher_app_key'] }}', {
                cluster: '{{ $config['pusher_app_cluster'] }}',
                forceTLS: true
            });

            // Subscribe to my-channel for test events
            const testChannel = pusher.subscribe('my-channel');
            testChannel.bind('my-event', function(data) {
                console.log('Received test event:', data);
                alert(JSON.stringify(data));
            });

            // Subscribe to cardknox-payments channel for Cardknox events
            const cardknoxChannel = pusher.subscribe('cardknox-payments');
            cardknoxChannel.bind('CardknoxPaymentCompleted', function(data) {
                console.log('Received CardknoxPaymentCompleted event:', data);
                alert('Triggered Cardknox Test Event!\n\nResponse Data:\n' + JSON.stringify(data.paymentData, null, 2));
            });

            // Log connection states
            pusher.connection.bind('connected', () => {
                console.log('Connected to Pusher');
                document.getElementById('status').textContent = 'Connected to Pusher';
            });

            pusher.connection.bind('error', (error) => {
                console.error('Pusher connection error:', error);
                document.getElementById('status').textContent = 'Error: ' + error.message;
            });

            pusher.connection.bind('disconnected', () => {
                console.log('Disconnected from Pusher');
                document.getElementById('status').textContent = 'Disconnected from Pusher';
            });
        @else
            document.getElementById('status').textContent = 'Error: Pusher cluster is not configured';
        @endif
    </script>
</head>
<body>
    <h1>Pusher Debug Page</h1>
    <div>
        <h2>Configuration:</h2>
        <pre>{{ json_encode($config, JSON_PRETTY_PRINT) }}</pre>
    </div>
    <div>
        <h2>Connection Status:</h2>
        <p id="status">Connecting...</p>
    </div>
    <div>
        <h2>Environment Variables:</h2>
        <p>PUSHER_APP_CLUSTER: {{ env('PUSHER_APP_CLUSTER') }}</p>
        <p>PUSHER_HOST: {{ env('PUSHER_HOST') }}</p>
    </div>
    <div>
        <h2>Test Events</h2>
        <button onclick="fetch('/test-event')">Trigger Test Event</button>
        <button onclick="fetch('/test-cardknox-event')">Trigger Cardknox Test Event</button>
    </div>
</body>
</html> 