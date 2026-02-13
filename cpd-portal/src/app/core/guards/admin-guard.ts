import { HttpInterceptorFn } from '@angular/common/http';

const CSRF_STORAGE_KEY = 'csrfToken';

import { inject } from '@angular/core';
import { CanActivateFn, Router } from '@angular/router';
import { catchError, map, of } from 'rxjs';
import { AuthService } from '../services/auth.service';

export const adminGuard: CanActivateFn = () => {
  const router = inject(Router);
  const authService = inject(AuthService);

  return authService.getMe().pipe(
    map((res) => {
      if (res.user?.access_level === 'admin') {
        return true;
      }
      return router.createUrlTree(['/login']);
    }),
    catchError(() => of(router.createUrlTree(['/login'])))
  );
};
