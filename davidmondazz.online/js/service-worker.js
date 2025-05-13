/**
 * Service Worker for Medication Tracking App
 * Provides offline functionality and caching
 */

const CACHE_NAME = 'med-track-cache-v1';
const URLS_TO_CACHE = [
  '/',
  '/index.php',
  '/css/bootstrap.min.css',
  '/css/style.css',
  '/js/jquery-3.6.0.min.js',
  '/js/med-notifications.js',
  '/img/icon.png'
];

// Install event - cache essential files
self.addEventListener('install', event => {
  event.waitUntil(
    caches.open(CACHE_NAME)
      .then(cache => {
        console.log('Caching app resources');
        return cache.addAll(URLS_TO_CACHE);
      })
  );
});

// Activate event - clean up old caches
self.addEventListener('activate', event => {
  event.waitUntil(
    caches.keys().then(cacheNames => {
      return Promise.all(
        cacheNames.map(cacheName => {
          if (cacheName !== CACHE_NAME) {
            console.log('Removing old cache:', cacheName);
            return caches.delete(cacheName);
          }
        })
      );
    })
  );
});

// Fetch event - serve from cache, fall back to network
self.addEventListener('fetch', event => {
  event.respondWith(
    // Try to get from cache
    caches.match(event.request)
      .then(response => {
        if (response) {
          return response; // Return cached response
        }
        
        // If not in cache, try network
        return fetch(event.request).then(networkResponse => {
          // Don't cache API responses
          if (
            !event.request.url.includes('get_medications.php') && 
            !event.request.url.includes('update_notification.php') &&
            !event.request.url.includes('update_medication.php')
          ) {
            // Clone the response as it can only be consumed once
            const responseToCache = networkResponse.clone();
            
            // Cache network response for future use
            caches.open(CACHE_NAME).then(cache => {
              cache.put(event.request, responseToCache);
            });
          }
          
          return networkResponse;
        });
      })
      .catch(() => {
        // If both cache and network fail, serve offline page
        if (event.request.mode === 'navigate') {
          return caches.match('/offline.html');
        }
      })
  );
});

// Handle push notifications
self.addEventListener('push', event => {
  const data = event.data.json();
  
  const options = {
    body: data.message,
    icon: 'img/icon.png',
    vibrate: [100, 50, 100],
    data: {
      dateOfArrival: Date.now(),
      primaryKey: 1
    },
    actions: [
      { action: 'explore', title: 'View Details' }
    ]
  };
  
  event.waitUntil(
    self.registration.showNotification(data.title, options)
  );
});

// Handle notification click events
self.addEventListener('notificationclick', event => {
  event.notification.close();
  
  if (event.action === 'explore') {
    // Open specific page for this notification
    event.waitUntil(
      clients.openWindow('/index.php')
    );
  } else {
    // Default action - open app
    event.waitUntil(
      clients.openWindow('/')
    );
  }
});

// Listen for messages from the main thread
self.addEventListener('message', event => {
  if (event.data.type === 'SKIP_WAITING') {
    self.skipWaiting();
  }
});