import { Component, OnInit, ChangeDetectorRef, HostListener, signal, WritableSignal } from '@angular/core'; 
import { CommonModule } from '@angular/common';
import { RouterModule } from '@angular/router';
import Chart from 'chart.js/auto';
import { PageHeader } from '../../components/page-header/page-header';

// 🟢 1. استيراد السيرفيس ديال ياسين (الـ SDK)
import { IdpReqService } from 'idp-com-sdk';

@Component({
  selector: 'app-dashboard',
  standalone: true,
  imports: [CommonModule, RouterModule, PageHeader],
  templateUrl: './dashboard.html',
})
export class Dashboard implements OnInit {
  fluxChart: any;
  isProfileDropdownOpen = false;
  isExerciceMenuOpen = false;
  isLoading = false;
  isSendingRappel: string | null = null;

  currentIndex: number = 0;

  exercices: any[] = [];
  exerciceSelectionne: any = null;
  budgetTab: 'previsionnel' | 'travaux' = 'previsionnel';

  residence = { nom: '...', adresse: '...', exercice: '...', periode: '...', gerant: '...', role: '...' };
  budget = {
    previsionnel: { total: '0', depenses: '0', pourcentage: 0 },
    travaux:      { total: '0', depenses: '0', pourcentage: 0 }
  };
  trimestres: { previsionnel: any[], travaux: any[] } = { previsionnel: [], travaux: [] };
  soldes: any[] = [];
  totalDu: string = '0';

  // ── Toast ──
  showToastNotif = false;
  toastMessage   = '';
  toastType: 'success' | 'error' = 'success';
  private toastTimeout: any;

  // ── Confirm modal ──
  isConfirmModalOpen   = false;
  soldeToRelance: any  = null;

  // 🟢 2. حيدنا baseUrl القديم حيت SDK كيتكلف بيه اوتوماتيكيا
  
  constructor(
    private idpreqservice: IdpReqService, // 🟢 دخلنا السيرفيس ديال Gateway
    private cdr: ChangeDetectorRef
  ) {}

  ngOnInit() {
    this.chargerDashboard();
  }

  @HostListener('document:click', ['$event'])
  onDocumentClick(event: MouseEvent) {
    const target = event.target as HTMLElement;
    if (!target.closest('.exercice-dropdown')) {
      this.isExerciceMenuOpen = false;
    }
  }

  // ── Toast ──
  showToast(message: string, type: 'success' | 'error' = 'success') {
    this.toastMessage = message;
    this.toastType    = type;
    this.showToastNotif = true;
    this.cdr.detectChanges();
    if (this.toastTimeout) clearTimeout(this.toastTimeout);
    this.toastTimeout = setTimeout(() => {
      this.showToastNotif = false;
      this.cdr.detectChanges();
    }, 3500);
  }

  // ── Confirm modal ──
  ouvrirConfirmRelance(solde: any) {
    this.soldeToRelance      = solde;
    this.isConfirmModalOpen  = true;
    this.cdr.detectChanges();
  }

  fermerConfirmModal() {
    this.isConfirmModalOpen = false;
    this.soldeToRelance     = null;
    this.cdr.detectChanges();
  }

  confirmerRelance() {
    if (!this.soldeToRelance) return;
    const solde = this.soldeToRelance;
    this.fermerConfirmModal();
    this.relancerCoproprietaire(solde);
  }

  onExerciceChange() {
    this.currentIndex = 0;
    this.chargerDashboard();
  }

  chargerDashboard() {
    this.isLoading = true;

    // 🟢 3. جمعنا Payload كيفما كان، وزدنا فيه residence_id حيت Laravel كيقلب عليها فـ input
    const activePropId = localStorage.getItem('active_propriete_id') || '';
    const payload: any = {
      residence_id: activePropId
    };

    if (this.exerciceSelectionne) {
      payload.se_identifier = this.exerciceSelectionne;
    }

    // 🟢 4. ياسين داير f l'architecture ديالو نخدمو بـ Signals لـ status
    const actionSignal: WritableSignal<boolean> = signal(true);

    // 🟢 5. صيفطنا لا روكيت بـ SDK ديال ياسين عوض HttpClient
    this.idpreqservice.forwardRequest(
      'api/dashboard/data', // المسار فـ الباكاند ديالك
      'post', 
      payload, 
      actionSignal, 
      (res: any) => { // Next Callback (Succès)
        if (res.success || res.data) {
          // حسب الفورما ديالك، البيانات كتكون وسط data
          const responseData = res.data || res;
          
          this.residence = responseData.residence || this.residence;

          if (responseData.budget) {
            const b = responseData.budget;
            this.budget = {
              previsionnel: {
                total:       b.previsionnel    || '0',
                depenses:    b.depenses_prev   || '0',
                pourcentage: b.pourcentage_prev || 0
              },
              travaux: {
                total:       b.travaux         || '0',
                depenses:    b.depenses_trav   || '0',
                pourcentage: b.pourcentage_trav || 0
              }
            };
          }

          if (responseData.trimestres) {
            this.trimestres = responseData.trimestres;
          } else {
            this.trimestres = { previsionnel: responseData.trimestres || [], travaux: [] };
          }

          this.soldes  = responseData.soldes  || [];
          this.totalDu = responseData.totalDu || '0';

          if (responseData.exercice_selectionne) {
            this.exerciceSelectionne = typeof responseData.exercice_selectionne === 'object'
              ? (responseData.exercice_selectionne.id || responseData.exercice_selectionne.se_identifier)
              : responseData.exercice_selectionne;
          } else {
            this.exerciceSelectionne = this.exercices.length > 0 ? this.exercices[0].id : null;
          }

          if (responseData.exercices && Array.isArray(responseData.exercices)) {
            this.exercices = responseData.exercices.map((ex: any) => ({
              ...ex,
              id:        ex.se_identifier || ex.id || ex.identifier,
              dateDebut: this.formaterDate(ex.start_date || ex.dateDebut),
              dateFin:   this.formaterDate(ex.end_date   || ex.dateFin)
            }));
          }

          if (responseData.flux_tresorerie) {
            setTimeout(() => this.initFluxChart(responseData.flux_tresorerie), 100);
          }
        }

        this.isLoading = false;
        this.cdr.detectChanges();
      },
      (err: any) => { // Error Callback
        console.error("❌ Erreur Dashboard via Gateway:", err);
        this.soldes     = [];
        this.trimestres = { previsionnel: [], travaux: [] };
        this.isLoading  = false;
        this.cdr.detectChanges();
      }
    );
  }

  formaterDate(dateString: string): string {
    if (!dateString) return 'Date inconnue';
    try {
      const d = new Date(dateString);
      if (isNaN(d.getTime())) return dateString;
      return d.toLocaleDateString('fr-FR', { day: 'numeric', month: 'short', year: 'numeric' });
    } catch {
      return dateString;
    }
  }

  relancerCoproprietaire(solde: any) {
    const identifier = solde.su_identifier || String(solde.user_id);
    if (!identifier) {
      this.showToast("Identifiant du copropriétaire introuvable.", "error");
      return;
    }

    this.isSendingRappel = identifier;
    this.cdr.detectChanges();

    const payload = {
      su_identifier: identifier,
      user_id: solde.user_id
    };

    const actionSignal: WritableSignal<boolean> = signal(true);

    // 🟢 6. التعديل ديال Relance باش يمشي حتى هو من الـ Gateway
    this.idpreqservice.forwardRequest(
      'api/impayes/envoyer-rappel',
      'post',
      payload,
      actionSignal,
      (res: any) => {
        this.showToast(
          res.success ? "Rappel envoyé avec succès !" : (res.message || "Erreur lors de l'envoi."),
          res.success ? "success" : "error"
        );
        this.isSendingRappel = null;
        this.cdr.detectChanges();
      },
      (err: any) => {
        this.showToast(err.error?.message || "Erreur lors de l'envoi du rappel.", "error");
        this.isSendingRappel = null;
        this.cdr.detectChanges();
      }
    );
  }

  minVal(a: number, b: number): number {
    return Math.min(a, b);
  }

  initFluxChart(fluxData: any) {
    const canvas = document.getElementById('lineChartFlux') as HTMLCanvasElement;
    if (!canvas || !fluxData) return;
    if (this.fluxChart) this.fluxChart.destroy();

    this.fluxChart = new Chart(canvas, {
      type: 'line',
      data: {
        labels: fluxData.labels,
        datasets: [
          {
            label: 'Encaissements',
            data: fluxData.encaissements,
            borderColor: '#10B981',
            backgroundColor: 'rgba(16, 185, 129, 0.1)',
            borderWidth: 2,
            tension: 0.4,
            fill: true
          },
          {
            label: 'Dépenses',
            data: fluxData.depenses,
            borderColor: '#EF4444',
            backgroundColor: 'rgba(239, 68, 68, 0.1)',
            borderWidth: 2,
            tension: 0.4,
            fill: true
          }
        ]
      },
      options: {
        responsive: true,
        maintainAspectRatio: false,
        plugins: { legend: { display: false } },
        scales: {
          y: { beginAtZero: true, grid: { color: '#f3f4f6' } },
          x: { grid: { display: false } }
        }
      }
    });
  }
}