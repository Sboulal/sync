import { Component, OnInit, ViewEncapsulation, inject, signal, WritableSignal } from '@angular/core';
import { CommonModule, ViewportScroller } from '@angular/common';
import { RouterModule, Router, ActivatedRoute } from '@angular/router';
// 🟢 Zidna l'imports dyal l'IDP w Storage
import { IdpReqService } from 'idp-com-sdk';
import { StorageService } from '../../services/storage.service'; // T2akdi mn l'chemin dyal StorageService

@Component({
  selector: 'app-landing',
  standalone: true,
  imports: [CommonModule, RouterModule],
  templateUrl: './landing.html',
  styleUrls: ['./landing.css'],
  encapsulation: ViewEncapsulation.None,
})
export class Landing implements OnInit {
  // 🟢 Signal dyal l'loading
  isLoading: WritableSignal<boolean> = signal(false);

  private idpreqservice = inject(IdpReqService);
  private storageservice = inject(StorageService);
  private route = inject(ActivatedRoute);

  constructor(
    private viewportScroller: ViewportScroller,
    private router: Router
  ) {}

  // 🟢 Zidna ngOnInit bach t-9ra l'code mni t-rej3i mn l'IDP
  ngOnInit() {
    this.route.queryParams.subscribe(params => {
      const code = params['authorization_code']; 
      const email = params['email'];

      // Ila rje3ti mn l'IDP w l9ina l'code f l'URL:
      if (code) {
        this.isLoading.set(true);
        
        this.idpreqservice.tradeCodeForToken(
          code,
          email || '',
          this.isLoading,
          (res: any) => {
            console.log("🟢 Réponse IDP f Landing:", res); 
            const token = res['access_token'] || (res.data ? res.data.access_token : null);
            
            if (token) {
              console.log("✅ Token l9inah f Landing! Ghadi n-sauvegardiwh:", token);
              this.storageservice.setItem('access_token', token);
              localStorage.setItem('is_logged_in', 'true');
              
              // N-ms7ou l'code mn l'URL w n-mchiw l'Home
              this.router.navigate([], { queryParams: {}, replaceUrl: true }).then(() => {
                this.router.navigateByUrl('/home');
              });
            } else {
              console.error("❌ Mal9itch access_token f la réponse dyal l'IDP!", res);
              this.isLoading.set(false);
            }
          },
          (err: any) => {
            console.error("❌ Erreur IDP f Landing:", err);
            this.isLoading.set(false);
          }
        );
      }
    });
  }

  scrollTo(elementId: string): void {
    this.viewportScroller.scrollToAnchor(elementId);
  }

  allerVersLogin() {
    this.router.navigate(['/login']);
  }
}