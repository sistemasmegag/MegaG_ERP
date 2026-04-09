<?php
require_once __DIR__ . '/helpers/firebase.php';

header('Content-Type: application/javascript; charset=utf-8');
$cfg = mg_firebase_public_config();
?>
self.MG_FIREBASE_SW_CONFIG = <?= json_encode($cfg, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) ?>;

if (self.MG_FIREBASE_SW_CONFIG && self.MG_FIREBASE_SW_CONFIG.enabled) {
  importScripts('https://www.gstatic.com/firebasejs/10.13.2/firebase-app-compat.js');
  importScripts('https://www.gstatic.com/firebasejs/10.13.2/firebase-messaging-compat.js');

  firebase.initializeApp({
    apiKey: self.MG_FIREBASE_SW_CONFIG.api_key,
    authDomain: self.MG_FIREBASE_SW_CONFIG.auth_domain || undefined,
    projectId: self.MG_FIREBASE_SW_CONFIG.project_id,
    storageBucket: self.MG_FIREBASE_SW_CONFIG.storage_bucket || undefined,
    messagingSenderId: self.MG_FIREBASE_SW_CONFIG.messaging_sender_id,
    appId: self.MG_FIREBASE_SW_CONFIG.app_id,
    measurementId: self.MG_FIREBASE_SW_CONFIG.measurement_id || undefined
  });

  const messaging = firebase.messaging();

  messaging.onBackgroundMessage((payload) => {
    const notification = payload.notification || {};
    const data = payload.data || {};
    const title = notification.title || data.title || 'Notificacao';
    const options = {
      body: notification.body || data.body || '',
      data: {
        url: (payload.fcmOptions && payload.fcmOptions.link) || data.url || ''
      }
    };

    self.registration.showNotification(title, options);
  });
}

self.addEventListener('notificationclick', (event) => {
  const url = (event.notification && event.notification.data && event.notification.data.url) || '';
  event.notification.close();

  if (!url) {
    return;
  }

  event.waitUntil(clients.matchAll({ type: 'window', includeUncontrolled: true }).then((windowClients) => {
    for (const client of windowClients) {
      if (client.url === url && 'focus' in client) {
        return client.focus();
      }
    }

    if (clients.openWindow) {
      return clients.openWindow(url);
    }

    return undefined;
  }));
});
