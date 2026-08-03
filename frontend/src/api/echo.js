import Echo from 'laravel-echo'
import Pusher from 'pusher-js'
import client from './client'

window.Pusher = Pusher

let echoInstance = null

export function disconnectEcho() {
  if (echoInstance) {
    try {
      echoInstance.disconnect()
    } catch (e) {
      // Ignore disconnect errors
    }
    echoInstance = null
  }
}

export function getEcho() {
  if (!echoInstance) {
    echoInstance = new Echo({
      broadcaster: 'reverb',
      key: import.meta.env.VITE_REVERB_APP_KEY || 'chatapp-key',
      wsHost: import.meta.env.VITE_REVERB_HOST || window.location.hostname,
      wsPort: import.meta.env.VITE_REVERB_PORT || 8080,
      wssPort: import.meta.env.VITE_REVERB_PORT || 8080,
      forceTLS: false,
      enabledTransports: ['ws', 'wss'],
      authorizer: (channel) => {
        return {
          authorize: (socketId, callback) => {
            client.post('/broadcasting/auth', {
              socket_id: socketId,
              channel_name: channel.name
            })
            .then(response => {
              callback(false, response.data)
            })
            .catch(error => {
              callback(true, error)
            })
          }
        }
      }
    })
  }
  return echoInstance
}
