import { Component, OnInit, inject, signal, WritableSignal } from '@angular/core';
import { CommonModule } from '@angular/common';
import { ActivatedRoute, Router } from '@angular/router'; 
import { IdpReqService } from 'idp-com-sdk';
import { StorageService } from '../../services/storage.service';

@Component({
  selector: 'app-login',
  standalone: true,
  imports: [CommonModule],
  templateUrl: './login.html',
  styleUrls: ['./login.css']
})
export class Login implements OnInit {
  isLoading: WritableSignal<boolean> = signal(false);
  errorMessage: string = '';

  private idpreqservice = inject(IdpReqService);
  private storageservice = inject(StorageService);
  private route = inject(ActivatedRoute);
  private router = inject(Router);

  ngOnInit() {
    this.route.queryParams.subscribe(params => {
      const code = params['authorization_code']; 
      const email = params['email'];

      if (code) {
        this.isLoading.set(true);
        
        this.idpreqservice.tradeCodeForToken(
          code,
          email || '',
          this.isLoading,
          (res: any) => {
        
            console.log("🟢 Réponse IDP (tradeCodeForToken):", res); 
            
           
            const token = res['access_token'] || (res.data ? res.data.access_token : null);
            
            if (token) {
              console.log("✅ Token l9inah! Ghadi n-sauvegardiwh:", token);
              this.storageservice.setItem('access_token', token);
              localStorage.setItem('is_logged_in', 'true');
              
              this.router.navigate([], { queryParams: {}, replaceUrl: true }).then(() => {
                this.router.navigateByUrl('/home');
              });
            } else {
              console.error("❌ Mal9itch access_token f la réponse dyal l'IDP!", res);
              this.errorMessage = "Erreur: Jeton d'accès introuvable.";
              this.isLoading.set(false);
            }
          },
          (err: any) => {
            console.error("❌ Erreur IDP:", err);
            this.errorMessage = "Erreur lors de l'authentification avec l'IDP.";
            this.isLoading.set(false);
          }
        );
      }
    });
  }

  onLoginClick() {
    this.idpreqservice.authenticateMe();
  }
}