import { HttpInterceptorFn } from '@angular/common/http';

export const authInterceptor: HttpInterceptorFn = (req, next) => {
  console.log('🚀 Interceptor dakhla lih l-requête:', req.url);

  // 🟢 ملي استعملنا HttpOnly Cookies، المتصفح كيتكلف يصيفط التوكن بوحدو
  // خاصنا غير نعطيوه الضوء الأخضر بهاد withCredentials
  const cloned = req.clone({
    withCredentials: true
  });
  
  return next(cloned);
};