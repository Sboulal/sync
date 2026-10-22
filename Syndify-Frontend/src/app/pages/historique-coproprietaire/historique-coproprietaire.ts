import { Component, OnInit, ChangeDetectorRef, signal, WritableSignal } from '@angular/core'; 
import { CommonModule } from '@angular/common';
import { ActivatedRoute, Router } from '@angular/router'; 
import { PageHeader } from '../../components/page-header/page-header'; 
import { FormsModule } from '@angular/forms';

// 🟢 استيراد الـ SDK
import { IdpReqService } from 'idp-com-sdk'; 

@Component({
  selector: 'app-historique-coproprietaire',
  standalone: true,
  imports: [CommonModule, PageHeader, FormsModule],
  templateUrl: './historique-coproprietaire.html',
})
export class HistoriqueCoproprietaire implements OnInit {
  
  residence = { nom: 'Chargement...', adresse: '...' };
  userId: string | null = null;
  isLoading: boolean = false;

  coproprietaire: any = null;
  exercicesPasses: any[] = [];
  exerciceSelectionne: any = ''; 
  
  totalEncaissements: number = 0;
  totalDepenses: number = 0;
  operations: any[] = [];

  isExerciceMenuOpen: boolean = false;

  // 🟢 VARIABLES DYAL L-POP-UP (TOAST)
  showNotification: boolean = false;
  notificationMessage: string = '';
  notificationType: 'success' | 'error' = 'success';
  notificationTimeout: any;

  // 🟢 Signal ديال الـ Gateway
  actionSignal: WritableSignal<boolean> = signal(false);

  constructor(
    private idpreqservice: IdpReqService, // 🟢 دخلنا الـ SDK
    private route: ActivatedRoute,
    private router: Router,
    private cdr: ChangeDetectorRef
  ) {
    const nav = this.router.getCurrentNavigation();
    this.userId = nav?.extras?.state?.['user_id'] || null;
  }

  ngOnInit() {
    this.userId = this.route.snapshot.paramMap.get('user_id'); 
    if (!this.userId) {
        this.router.navigate(['/liste-coproprietes']);
        return;
    }
    this.chargerHistorique();
  }

  // 🟢 Helper باش نجبدو ID الإقامة
  private getActivePropId() {
    return localStorage.getItem('active_propriete_id') || '';
  }

  // 🟢 FONCTION DYAL L-POP-UP
  showToast(message: string, type: 'success' | 'error' = 'success') {
    this.notificationMessage = message;
    this.notificationType = type;
    this.showNotification = true;
    this.cdr.detectChanges();

    if (this.notificationTimeout) {
      clearTimeout(this.notificationTimeout);
    }
    this.notificationTimeout = setTimeout(() => {
      this.showNotification = false;
      this.cdr.detectChanges();
    }, 3000);
  }

  changerExercice(id: any) {
    this.exerciceSelectionne = id;
    this.isExerciceMenuOpen = false; 
    this.chargerHistorique(); 
  }

  chargerHistorique() {
    if (!this.userId) return;
    this.isLoading = true;

    // 🟢 قادينا Payload كيفما كيطلب Backend
    const payload: any = {
      user_id: this.userId,
      residence_id: this.getActivePropId()
    };

    if (this.exerciceSelectionne) {
        payload.se_identifier = this.exerciceSelectionne;
    }
    
    // 🟢 استعملنا SDK ديال ياسين
    this.idpreqservice.forwardRequest(
      'api/coproprietaires/historique',
      'post',
      payload,
      this.actionSignal,
      (res: any) => {
        const resData = res.data || res;
        if (resData.success) {
          const d = resData.data || {};
          
          if (resData.residence) {
            this.residence = resData.residence;
          } else if (d.residence) {
            this.residence = d.residence;
          }

          this.coproprietaire    = d.coproprietaire    || null;
          this.exercicesPasses   = d.exercices         || [];
          this.exerciceSelectionne = d.exercice_selectionne || this.exerciceSelectionne;
          this.totalEncaissements  = d.total_encaissements  || 0;
          this.totalDepenses       = d.total_depenses       || 0;
          this.operations          = d.operations           || [];
        }
        this.isLoading = false;
        this.cdr.detectChanges(); 
      },
      (err: any) => {
        console.error("❌ Erreur API:", err);
        this.showToast("Erreur lors du chargement de l'historique.", "error");
        this.isLoading = false;
        this.cdr.detectChanges();
      }
    );
  }

  telechargerDoc(url: string) {
    if (!url) {
      this.showToast("Aucun document disponible pour cette opération.", "error");
      return;
    }
    window.open('https://api.syndify.nomade-cloud.com/storage/' + url, '_blank');
  }

  retour() {
    this.router.navigate(['/liste-coproprietes']); 
  }
}