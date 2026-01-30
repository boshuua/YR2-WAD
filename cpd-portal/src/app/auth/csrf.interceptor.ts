import { HttpInterceptorFn } from '@angular/common/http';

const CSRF_STORAGE_KEY = 'csrfToken';

export const csrfInterceptor: HttpInterceptorFn = (req, next) => {
  const method = req.method.toUpperCase();
  const isUnsafe = method !== 'GET' && method !== 'HEAD' && method !== 'OPTIONS';

  const token = sessionStorage.getItem(CSRF_STORAGE_KEY) ?? '';

  let nextReq = req.clone({
    withCredentials: true
  });

  if (isUnsafe && token) {
    nextReq = nextReq.clone({
      setHeaders: {
        'X-CSRF-Token': token
      }
    });
  }

  return next(nextReq);
};
