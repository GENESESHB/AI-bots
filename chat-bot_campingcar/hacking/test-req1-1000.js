import http from 'k6/http';
import { check } from 'k6';

export const options = {
  scenarios: {
    step_test: {
      executor: 'shared-iterations',
      vus: 1,             // just 1 VU to make requests in order
      iterations: 1001,   // 1000 + 1 (because we start at 0)
      maxDuration: '5m',
    },
  },
};

export default function () {
  // __ITER starts from 0, so iteration 0 → "/"
  let path = __ITER === 0 ? '' : `/${__ITER}`;
  const url = `http://192.168.1.3:3000${path}`;

  const res = http.get(url);
  check(res, {
    'status is 200': (r) => r.status === 200,
  });
}

