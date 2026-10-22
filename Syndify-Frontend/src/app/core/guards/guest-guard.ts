import { inject } from '@angular/core';
import { CanActivateFn, Router } from '@angular/router';

export const guestGuard: CanActivateFn = (route, state) => {
  const router = inject(Router);
  
  // 🟢 كنقلبو غير واش مسجلين الدخول
  const isLoggedIn = localStorage.getItem('is_logged_in'); 

  if (isLoggedIn === 'true') {
    router.navigate(['/dashboard']);
    return false;
  } else {
   
    return true;
  }
};