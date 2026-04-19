import axios from 'axios';
import Echo from 'laravel-echo';
import Pusher from 'pusher-js';

window.axios = axios;

window.axios.defaults.headers.common['X-Requested-With'] = 'XMLHttpRequest';

window.Pusher = Pusher;

const token = localStorage.getItem('auxfin_token');

function createEchoClient({ key, cluster }) {
	if (!key || !cluster) {
		return null;
	}

	return new Echo({
		broadcaster: 'pusher',
		key,
		cluster,
		forceTLS: true,
		authEndpoint: '/broadcasting/auth',
		auth: {
			headers: token ? { Authorization: `Bearer ${token}` } : {},
		},
	});
}

window.EchoMain = createEchoClient({
	key: import.meta.env.VITE_PUSHER_APP_KEY,
	cluster: import.meta.env.VITE_PUSHER_APP_CLUSTER,
});

window.EchoNotifications = createEchoClient({
	key: import.meta.env.VITE_PUSHER_NOTIFICATION_APP_KEY,
	cluster: import.meta.env.VITE_PUSHER_NOTIFICATION_APP_CLUSTER,
});

window.EchoChat = createEchoClient({
	key: import.meta.env.VITE_PUSHER_CHAT_APP_KEY,
	cluster: import.meta.env.VITE_PUSHER_CHAT_APP_CLUSTER,
});

window.EchoInsights = createEchoClient({
	key: import.meta.env.VITE_PUSHER_INSIGHTS_APP_KEY,
	cluster: import.meta.env.VITE_PUSHER_INSIGHTS_APP_CLUSTER,
});

window.Echo = window.EchoMain ?? window.EchoNotifications ?? window.EchoChat ?? window.EchoInsights ?? null;

window.configureEchoAuth = (authToken = null) => {
	const headers = authToken ? { Authorization: `Bearer ${authToken}` } : {};
	[window.EchoMain, window.EchoNotifications, window.EchoChat, window.EchoInsights, window.Echo]
		.filter(Boolean)
		.forEach((client) => {
			client.connector.options.auth = { headers };
		});
};

window.configureEchoAuth(token);
