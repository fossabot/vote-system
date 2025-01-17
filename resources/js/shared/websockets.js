import Echo from 'laravel-echo'
import Pusher from 'pusher-js'

/**
 * @type {null|Echo} instance
 */
let instance = null

/**
 * @returns {Echo}
 */
const getConnection = () => {
    if (!instance) {
        const config = window.__PUSHER_CONFIG__
        instance = new Echo({
            broadcaster: 'pusher',
            client: new Pusher(config.PUSHER_APP_KEY, {
                wsHost: window.location.hostname,
                statsHost: window.location.hostname,
                httpHost: window.location.hostname,
                wsPort: 6001,
                forceTLS: window.location.protocol === 'https:',
                disableStats: true,
                authEndpoint: '/broadcasting/auth',
            }),
        })
    }
    return instance
}

export default getConnection
