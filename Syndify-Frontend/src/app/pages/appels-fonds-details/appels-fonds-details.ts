import { Component, OnInit, ChangeDetectorRef } from '@angular/core';
import { CommonModule, Location } from '@angular/common'; 
import { PageHeader } from '../../components/page-header/page-header';
import { HttpClient } from '@angular/common/http';
import { ActivatedRoute, Router } from '@angular/router';

@Component({
  selector: 'app-appels-fonds-details',
  standalone: true,
  imports: [CommonModule, PageHeader],
  templateUrl: './appels-fonds-details.html',
})
export class AppelsFondsDetails implements OnInit {
  
  appelIdActuel: string = '';
  appelPrincipal: any = null;
  lignesProprietaires: any[] = [];
  isLoading: boolean = false;
  residenceInfo = { nom: '...', adresse: '...' };

  isConfirmModalOpen: boolean = false;
  confirmActionType: 'generer' | 'envoyer' | null = null;

  showNotification: boolean = false;
  notificationMessage: string = '';
  notificationType: 'success' | 'error' = 'success';
  notificationTimeout: any;

  // ========== ROLE ==========
  userRole: string = '';

  get isSyndic(): boolean {
    return this.userRole.toLowerCase().trim() === 'syndic';
  }
  // ==========================

  private baseUrl = 'http://51.178.87.234 :8021/api';

  constructor(
    private http: HttpClient,
    private cdr: ChangeDetectorRef,
    private route: ActivatedRoute,
    private router: Router,
    private location: Location 
  ) {}

  ngOnInit() {
    // 🟢 Jbed le role mn localStorage (clé 'user' ou 'role' 7sb ta7dida dyal app dyalk)
    this.userRole = localStorage.getItem('user_role') ?? '';
    console.log('🔑 user_role depuis localStorage:', `"${this.userRole}"`, '| isSyndic:', this.isSyndic);

    this.route.paramMap.subscribe(params => {
      const id = params.get('id');
      if (id) {
        this.appelIdActuel = id;
        this.chargerDetails();
      } else {
        this.retourListe();
      }
    });
  }

  retourListe() {
    this.location.back(); 
  }

  showToast(message: string, type: 'success' | 'error' = 'success') {
    this.notificationMessage = message;
    this.notificationType = type;
    this.showNotification = true;
    this.cdr.detectChanges();

    if (this.notificationTimeout) clearTimeout(this.notificationTimeout);
    
    this.notificationTimeout = setTimeout(() => {
      this.showNotification = false;
      this.cdr.detectChanges();
    }, 3000);
  }

chargerDetails() {
    this.isLoading = true;
    const payload = { af_identifier: this.appelIdActuel };

    this.http.post(`${this.baseUrl}/appels-fonds/details`, payload).subscribe({
      next: (res: any) => {
        if (res.success) {
          if (res.data?.residence)       this.residenceInfo = res.data.residence;
          else if (res.residence)        this.residenceInfo = res.residence;
          else if (res.data?.propriete)  this.residenceInfo = res.data.propriete;

          this.appelPrincipal = res.data.appel;

          // 🟢 حيدنا الشرط ديال isSyndic وغنعمرو الطابلو نيشان
          this.lignesProprietaires = (res.data.lignes || []).map((ligne: any) => {
              const provision = Number(ligne.montant_du) || 0;
              const soldeAvant = Number(ligne.solde_avant) || 0; 
              const soldeApres = soldeAvant - provision;

              return {
                coproprietaire: ligne.owner_name || ligne.email || 'Propriétaire Inconnu',
                soldeAvant: soldeAvant,
                provision: provision,
                soldeApres: soldeApres
              };
          });
        }
        this.isLoading = false;
        this.cdr.detectChanges();
      },
      error: (err) => {
        console.error('❌ Erreur Détails:', err);
        this.showToast("Erreur lors du chargement des détails.", "error");
        this.isLoading = false;
        this.cdr.detectChanges();
      }
    });
}
  genererAppel() {
    if (!this.appelPrincipal || this.appelPrincipal.is_generated) return;
    this.confirmActionType = 'generer';
    this.isConfirmModalOpen = true;
  }

  envoyerAppel() {
    if (!this.appelPrincipal || !this.appelPrincipal.is_generated || this.appelPrincipal.is_sent) return;
    this.confirmActionType = 'envoyer';
    this.isConfirmModalOpen = true;
  }

  fermerConfirmModal() {
    this.isConfirmModalOpen = false;
    this.confirmActionType = null;
  }

  confirmerAction() {
    if (!this.appelPrincipal || this.isLoading) return;

    this.isLoading = true;
    this.isConfirmModalOpen = false;

    // ── GÉNÉRER ──
    if (this.confirmActionType === 'generer') {
      const payload = {
        se_identifier: this.appelPrincipal.se_identifier,
        af_identifier: this.appelIdActuel
      };

      this.http.post(`${this.baseUrl}/appels-fonds/generer`, payload, { responseType: 'blob' }).subscribe({
        next: (res: Blob) => {
          this.showToast("PDFs générés avec succès.", "success");
          this.chargerDetails();

          const blob = new Blob([res], { type: 'application/zip' });
          const url  = window.URL.createObjectURL(blob);
          const a    = document.createElement('a');
          a.style.display = 'none';
          a.href     = url;
          a.download = `Appel_Fonds_${this.appelIdActuel}.zip`;
          document.body.appendChild(a);
          a.click();

          setTimeout(() => {
            window.URL.revokeObjectURL(url);
            a.remove();
            this.isLoading = false;
            this.cdr.detectChanges();
          }, 1000);
        },
        error: (err) => {
          if (err.error instanceof Blob) {
            err.error.text().then((text: string) => {
              try {
                const parsed = JSON.parse(text);
                this.showToast(parsed.message || "Erreur de génération", "error");
              } catch {
                this.showToast("Erreur de génération", "error");
              }
            });
          } else {
            this.showToast(err.error?.message || "Erreur lors de la génération", "error");
          }
          this.isLoading = false;
          this.cdr.detectChanges();
        }
      });
    }

    // ── ENVOYER ──
    else if (this.confirmActionType === 'envoyer') {
      this.http.post(`${this.baseUrl}/appels-fonds/envoyer`, {
        af_identifier: this.appelIdActuel
      }).subscribe({
        next: (res: any) => {
          if (res.success) {
            this.showToast(res.message || "Appel envoyé avec succès.", "success");
            this.chargerDetails();
          }
          this.isLoading = false;
          this.cdr.detectChanges();
        },
        error: (err) => {
          this.showToast(err.error?.message || "Erreur d'envoi", "error");
          this.isLoading = false;
          this.cdr.detectChanges();
        }
      });
    }
  }
}