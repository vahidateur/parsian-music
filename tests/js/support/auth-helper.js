/**
 * Authentication helper for JS property tests.
 *
 * Authenticates against the running Laravel application using session-based login
 * and returns a cookie string for subsequent authenticated requests.
 *
 * Environment variables:
 *   CALENDAR_API_URL — base URL (default: http://127.0.0.1:8000/admin/calendar/events)
 *   TEST_ADMIN_PHONE — admin phone number for login
 *   TEST_ADMIN_PASSWORD — admin password for login
 *   TEST_BASE_URL — base URL of the app (default: http://127.0.0.1:8000)
 */

const BASE_URL = process.env.TEST_BASE_URL ?? 'http://127.0.0.1:8000';

/**
 * Extract all Set-Cookie values from a response and merge them into a cookie jar object.
 */
function extractCookies(response, jar = {}) {
    const setCookies = response.headers.getSetCookie?.() ?? [];
    for (const raw of setCookies) {
        const [pair] = raw.split(';');
        const [name, ...rest] = pair.split('=');
        jar[name.trim()] = rest.join('=').trim();
    }
    return jar;
}

/**
 * Serialize a cookie jar object into a Cookie header string.
 */
function serializeCookies(jar) {
    return Object.entries(jar)
        .map(([k, v]) => `${k}=${v}`)
        .join('; ');
}

/**
 * Authenticate as an admin user and return the session cookie string.
 *
 * @returns {Promise<string>} Cookie header value for authenticated requests
 */
export async function authenticate() {
    const phone = process.env.TEST_ADMIN_PHONE;
    const password = process.env.TEST_ADMIN_PASSWORD;

    if (!phone || !password) {
        throw new Error(
            'TEST_ADMIN_PHONE and TEST_ADMIN_PASSWORD environment variables are required for authenticated property tests.'
        );
    }

    let jar = {};

    // Step 1: GET the login page to obtain XSRF-TOKEN and session cookies
    const loginPageResponse = await fetch(`${BASE_URL}/login`, {
        redirect: 'manual',
        headers: { Accept: 'text/html', Connection: 'close' },
    });
    jar = extractCookies(loginPageResponse, jar);
    // Fully drain the body so the HTTP client releases the socket cleanly.
    const loginPageHtml = await loginPageResponse.text();

    // Step 2: Prefer the plain CSRF token rendered in the login markup; the
    // XSRF-TOKEN cookie value is encrypted and cannot be used as a form token.
    const csrfToken = /<meta name="csrf-token" content="([^"]+)"/.exec(loginPageHtml)?.[1]
        ?? /name="_token"[^>]*value="([^"]+)"/.exec(loginPageHtml)?.[1]
        ?? null;

    if (!csrfToken) {
        throw new Error('Could not obtain a CSRF token from the login page.');
    }

    // Retained for the X-XSRF-TOKEN header expected by the framework.
    const xsrfToken = jar['XSRF-TOKEN']
        ? decodeURIComponent(jar['XSRF-TOKEN'])
        : null;

    if (!xsrfToken) {
        throw new Error('Could not obtain XSRF-TOKEN from login page.');
    }

    // Step 3: POST to /login with credentials
    const loginResponse = await fetch(`${BASE_URL}/login`, {
        method: 'POST',
        redirect: 'manual',
        headers: {
            'Content-Type': 'application/x-www-form-urlencoded',
            Accept: 'text/html',
            Connection: 'close',
            Cookie: serializeCookies(jar),
            'X-XSRF-TOKEN': xsrfToken,
        },
        body: new URLSearchParams({ phone, password, _token: csrfToken }).toString(),
    });

    jar = extractCookies(loginResponse, jar);
    // Fully drain the body so the HTTP client releases the socket cleanly.
    await loginResponse.text();

    // A successful login redirects (302); if we get 200 or 422 something went wrong.
    if (loginResponse.status !== 302) {
        throw new Error(
            `Login failed with status ${loginResponse.status}. Check TEST_ADMIN_PHONE and TEST_ADMIN_PASSWORD.`
        );
    }

    return serializeCookies(jar);
}
