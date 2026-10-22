import { Component, Input, OnInit } from '@angular/core';
import { CommonModule } from '@angular/common';
import { HttpClient } from '@angular/common/http';
import { Router, RouterModule } from '@angular/router';

@Component({
  selector: 'app-page-header',
  standalone: true,
  imports: [CommonModule,RouterModule],
  templateUrl: './page-header.html',
})
export class PageHeader implements OnInit {
  isProfileDropdownOpen: boolean = false;
  
  // 🟢 FIX : حيدنا السمية القديمة لي كانت Hardcoded
  @Input() parentPath: string = '';
  @Input() currentPath: string = '';
  @Input() title: string = '';
  @Input() statsText: string = '';
  @Input() statsSubText: string = '';
  @Input() description: string = '';

  private baseUrl = 'http://51.178.87.234 :8021/api';

  constructor(
    private http: HttpClient,
    private router: Router
  ) {}

  ngOnInit() {
    // 🟢 كنجبدو السمية الصحيحة من LocalStorage إيلا ماباساهاش الـ Parent
    if (!this.parentPath || this.parentPath === 'Résidence les jardins') {
      this.parentPath = localStorage.getItem('residence_nom') || 'Ma copropriété';
    }
  }

  Logout() {
    // 🟢 مابقيناش كنقلبو على التوكن فـ LocalStorage حيت راه فـ Cookie (HttpOnly)
    // غنصيفطو الريكويست نيشان للباكاند باش يمسح دوك لي Cookies
    
    this.http.post(`${this.baseUrl}/logout`, {}, {
      withCredentials: true // 🟢 هادي ضروووورية باش الباكاند يتعرف علينا ويمسح Cookies
    }).subscribe({
      next: (res: any) => {
        this.clearSessionAndRedirect();
      },
      error: (err: any) => {
        console.error('Erreur lors de la requête de déconnexion :', err);
        // واخا يوقع إيرور فـ السيرفور، خاصنا نخرجو اليوزر من الفرانتد
        this.clearSessionAndRedirect();
      }
    });   
  }

  // 🟢 دالة كتمسح الآثار ديال اليوزر من الفرانتد
  clearSessionAndRedirect() {
    // هادا هو المؤشر اللي كيعتمدو عليه الـ Guards دابا
    localStorage.removeItem('is_logged_in'); 
    
    // تقدري تمسحي أي حاجة خرى كنتي كتخبيها
    localStorage.removeItem('user_id');
    localStorage.removeItem('user_role');
    localStorage.removeItem('active_propriete_id');
    localStorage.removeItem('residence_nom');
    
    // إعادة التوجيه لصفحة تسجيل الدخول
    this.router.navigate(['/login']);
  }
}