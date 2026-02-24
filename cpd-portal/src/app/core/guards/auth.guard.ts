import { inject } from '@angular/core';
import { CanActivateFn, Router } from '@angular/router';
import { catchError, map, of } from 'rxjs';
import { AuthService } from '../services/auth.service';

export const authGuard: CanActivateFn = () => {
  const router = inject(Router);
  const authService = inject(AuthService);

  return authService.getMe().pipe(
    map((res) => {
      if (res.user) {
        return true;
      }
      return router.createUrlTree(['/login']);
    }),
    catchError(() => of(router.createUrlTree(['/login']))),
  );
};
