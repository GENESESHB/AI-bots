import http from 'k6/http';
import { check } from 'k6';

export const options = {
  scenarios: {
    small_ramp: {
      executor: 'shared-iterations',
      vus: 50,               // 50 virtual users sharing 1000 iterations
      iterations: 1000,     // TOTAL requests (iterations) across all VUs
      maxDuration: '5m',
    },
  },
  thresholds: {
    'http_req_failed': ['rate<0.05'],      // abort goal: keep failure rate < 5%
    'http_req_duration': ['p(95)<2000'],   // 95% under 2s - adjust for your app
  },
};

export default function () {
  const url = 'http://192.168.1.3:3000/';
  const res = http.get(url);
  check(res, {
    'status is 200': (r) => r.status === 200,
  });
}

