import http from 'k6/http';
import { check, group, sleep } from 'k6';

const baseUrl = (__ENV.BASE_URL || 'http://127.0.0.1:8010').replace(/\/$/, '');
const email = __ENV.K6_EMAIL;
const password = __ENV.K6_PASSWORD;

export const options = {
    scenarios: {
        student_dashboard: {
            executor: 'ramping-vus',
            startVUs: 1,
            stages: [
                { duration: '5s', target: 1 },
                { duration: '10s', target: 3 },
                { duration: '10s', target: 5 },
                { duration: '5s', target: 0 },
            ],
            gracefulRampDown: '2s',
        },
    },
    thresholds: {
        checks: ['rate>0.99'],
        http_req_failed: ['rate<0.01'],
        'http_req_duration{flow:dashboard}': ['p(95)<3000'],
    },
};

let authenticated = false;
let sessionCookieName = '';
let sessionCookie = '';

export default function () {
    if (!email || !password) {
        throw new Error('Set K6_EMAIL and K6_PASSWORD to a dedicated test student account.');
    }

    if (!authenticated) {
        group('test account login', function () {
            const page = http.get(`${baseUrl}/`);
            const token = page.body.match(/name="_token"\s+value="([^"]+)"/);

            check(page, {
                'login page loads': (response) => response.status === 200,
                'login form has a CSRF token': () => token !== null,
            });

            if (!token) return;

            const response = http.post(
                `${baseUrl}/login`,
                {
                    _token: token[1],
                    email,
                    password,
                },
                { redirects: 0 }
            );

            const cookieName = Object.keys(response.cookies).find((name) => name.toLowerCase().includes('session'));
            const sessionCookies = cookieName ? response.cookies[cookieName] : null;
            if (sessionCookies && sessionCookies.length > 0) {
                sessionCookieName = cookieName;
                sessionCookie = sessionCookies[0].value;
            }

            authenticated = check(response, {
                'test student login succeeds': (result) =>
                    result.status === 302 &&
                    (result.headers.Location || '').includes('/student/dashboard'),
            });

            if (authenticated && (!sessionCookieName || !sessionCookie)) {
                throw new Error('Successful login did not return a session cookie.');
            }
        });

        if (!authenticated) return;
    }

    // Keep the original encrypted Laravel session cookie; k6's default jar fails to reuse its rotated value here.
    http.cookieJar().set(baseUrl, sessionCookieName, sessionCookie, {
        path: '/',
        secure: baseUrl.startsWith('https://'),
    });

    group('student dashboard', function () {
        const response = http.get(`${baseUrl}/student/dashboard`, {
            tags: { flow: 'dashboard' },
        });

        check(response, {
            'dashboard responds successfully': (result) => result.status === 200,
            'dashboard content is present': (result) =>
                result.status === 200 && /<h1\b[^>]*>\s*Welcome\s+back\b/.test(result.body),
        });
    });

    sleep(1);
}
