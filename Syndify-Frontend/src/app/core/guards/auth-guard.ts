import { inject } from '@angular/core';
import { CanActivateFn, Router } from '@angular/router';

export const authGuard: CanActivateFn = (route, state) => {
  const router = inject(Router);
  
  // 🟢 كنقلبو غير واش مسجلين الدخول
  const isLoggedIn = localStorage.getItem('is_logged_in'); 

  if (isLoggedIn === 'true') {
    return true;
  } else {
    router.navigate(['/login']);
    return false;
  }
};