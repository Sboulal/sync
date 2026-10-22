import { Component, OnInit, ChangeDetectorRef } from '@angular/core';
import { CommonModule } from '@angular/common';
import { FormsModule } from '@angular/forms';
import { PageHeader } from '../../components/page-header/page-header';
import { BudgetService } from '../../services/budget'; 
import { ExerciceService } from '../../services/exercice'; 
import { CoproprietaireService } from '../../services/coproprietaire';
import { CleRepartitionService } from '../../services/cle-repartition';

@Component({
  selector: 'app-budgets-operations',
  standalone: true,
  imports: [CommonModule, FormsModule, PageHeader],
  templateUrl: './budgetsdepenses.html',
})
export class BudgetsOperations implements OnInit {
  
  activeTab: 'previsionnel' | 'travaux' = 'previsionnel';
  
  exercices: any[] = [];
  exerciceSelectionne: string = '';
  isExerciceMenuOpen: boolean = false;

  isLoading: boolean = false;

  // ✅ FIX: Stats séparés par tab
  statsParTab: {
    previsionnel: { budgetTotal: number, encaissements: number, depenses: number, reste: number, totalDu: number },
    travaux:      { budgetTotal: number, encaissements: number, depenses: number, reste: number, totalDu: number }
  } = {
    previsionnel: { budgetTotal: 0, encaissements: 0, depenses: 0, reste: 0, totalDu: 0 },
    travaux:      { budgetTotal: 0, encaissements: 0, depenses: 0, reste: 0, totalDu: 0 }
  };

  operationsParTab: { previsionnel: any[], travaux: any[] } = {
    previsionnel: [],
    travaux: []
  };

  paginationParTab: {
    previsionnel: { lastEncId: number, lastDepId: number },
    travaux: { lastEncId: number, lastDepId: number }
  } = {
    previsionnel: { lastEncId: 0, lastDepId: 0 },
    travaux: { lastEncId: 0, lastDepId: 0 }
  };

  isThereMore: boolean = false;
  residenceInfo = { nom: '...', adresse: '...' };

  isAddModalOpen: boolean = false;
  isSubmitting: boolean = false;
  
  proprietairesActifs: any[] = [];
  clesRepartition: any[] = [];

  newMouvement: any = {
    type_mouvement: 'encaissement',
    title: '',
    amount: null,
    date: '',
    type_charges: 'previsionnel',
    sub_type_charges: 'planifié',
    owner_id: '',
    cle_repartition_id: ''
  };

  isEditModalOpen: boolean = false;
  editMouvement: any = {};

  showNotification: boolean = false;
  notificationMessage: string = '';
  notificationType: 'success' | 'error' = 'success';
  notificationTimeout: any;

  isConfirmModalOpen: boolean = false;
  operationToDelete: any = null;

  constructor(
    private cdr: ChangeDetectorRef,
    private budgetService: BudgetService,
    private exerciceService: ExerciceService,
    private coproprietaireService: CoproprietaireService, 
    private cleService: CleRepartitionService            
  ) {}

  ngOnInit() {
    this.chargerExercices();
  }

  get stats() {
    return this.statsParTab[this.activeTab];
  }

  get operations(): any[] {
    return this.operationsParTab[this.activeTab];
  }

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

  chargerExercices() {
    this.exerciceService.getListe().subscribe({
      next: (res: any) => {
        if (res.success && res.data.length > 0) {
          this.exercices = res.data;
          const exEnCours = this.exercices.find(ex => ex.status === 'en cours');
          
          if (exEnCours) {
            this.exerciceSelectionne = exEnCours.se_identifier; 
          } else {
            this.exerciceSelectionne = this.exercices[0].se_identifier; 
          }
          this.chargerDonnees(true); 
        }
      },
      error: (err) => {
        console.error("❌ Erreur chargement Exercices:", err);
      }
    });
  }

  chargerDonnees(reset: boolean = false) {
    if (!this.exerciceSelectionne) return;

    const tab = this.activeTab;

    if (reset) {
      this.operationsParTab[tab] = [];
      this.paginationParTab[tab] = { lastEncId: 0, lastDepId: 0 };
    }

    this.isLoading = true;

    const { lastEncId, lastDepId } = this.paginationParTab[tab];
    
    const payload = {
      exercise: this.exerciceSelectionne,
      type: tab,
      last_enc_id: lastEncId,
      last_dep_id: lastDepId
    };

    this.budgetService.chargerDonnees(payload).subscribe({
      next: (res: any) => {
        if (res.success) {
          if (res.residence) {
            this.residenceInfo = res.residence;
          }

          const t = res.data.totaux;
          if (t) {
            this.statsParTab[tab].budgetTotal   = t.budget || 0;
            this.statsParTab[tab].encaissements = t.total_encaissements || 0;
            this.statsParTab[tab].depenses      = t.total_depenses || 0;
            this.statsParTab[tab].reste         = this.statsParTab[tab].budgetTotal - this.statsParTab[tab].depenses;
            this.statsParTab[tab].totalDu       = this.statsParTab[tab].budgetTotal - this.statsParTab[tab].encaissements;
          } else {
            this.statsParTab[tab] = { budgetTotal: 0, encaissements: 0, depenses: 0, reste: 0, totalDu: 0 };
          }

          this.operationsParTab[tab] = [
            ...this.operationsParTab[tab],
            ...res.data.operations
          ];

          this.paginationParTab[tab].lastEncId = res.data.pagination.last_enc_id;
          this.paginationParTab[tab].lastDepId = res.data.pagination.last_dep_id;
          this.isThereMore = res.data.pagination.is_there_more;
        }
        this.isLoading = false;
        this.cdr.detectChanges();
      },
      error: (err) => {
        this.isLoading = false;
        this.cdr.detectChanges();
      }
    });
  }

  onExerciceChange() {
    this.operationsParTab = { previsionnel: [], travaux: [] };
    this.paginationParTab = {
      previsionnel: { lastEncId: 0, lastDepId: 0 },
      travaux: { lastEncId: 0, lastDepId: 0 }
    };
    this.statsParTab = {
      previsionnel: { budgetTotal: 0, encaissements: 0, depenses: 0, reste: 0, totalDu: 0 },
      travaux:      { budgetTotal: 0, encaissements: 0, depenses: 0, reste: 0, totalDu: 0 }
    };
    this.chargerDonnees(true);
  }

  setTab(tab: 'previsionnel' | 'travaux') {
    this.activeTab = tab;
    if (this.operationsParTab[tab].length === 0) {
      this.chargerDonnees(true);
    }
  }

  chargerPlus() {
    this.chargerDonnees(false); 
  }

  nouveauMouvement() {
    this.newMouvement = {
      type_mouvement: 'encaissement',
      title: '',
      amount: null,
      date: new Date().toISOString().split('T')[0],
      type_charges: this.activeTab,
      sub_type_charges: 'planifié',
      owner_id: '',
      cle_repartition_id: ''
    };
    this.isAddModalOpen = true;
    this.chargerProprietairesEtCles(); 
  }

  closeAddModal() {
    this.isAddModalOpen = false;
    this.cdr.detectChanges();
  }

  chargerProprietairesEtCles() {
    this.coproprietaireService.getListe('tous').subscribe({
      next: (res: any) => {
        if (res.success && res.data) {
          this.proprietairesActifs = res.data;
        } else {
          this.proprietairesActifs = [];
        }
        this.cdr.detectChanges();
      },
      error: (err) => {
        console.error("❌ Erreur chargement Proprietaires:", err);
        this.proprietairesActifs = [];
        this.cdr.detectChanges();
      }
    });

    this.cleService.getListe().subscribe({
      next: (res: any) => {
        if (res.success && res.data) {
          this.clesRepartition = res.data;
        } else {
          this.clesRepartition = [];
        }
        this.cdr.detectChanges();
      }
    });
  }

  soumettreMouvement() {
    this.newMouvement.type_charges = this.activeTab;

    if (!this.newMouvement.title || !this.newMouvement.amount || !this.newMouvement.date) {
      this.showToast("Veuillez remplir tous les champs obligatoires.", "error");
      return;
    }
    if (this.newMouvement.type_mouvement === 'encaissement' && !this.newMouvement.owner_id) {
      this.showToast("Veuillez sélectionner un propriétaire pour cet encaissement.", "error");
      return;
    }
    
    this.isSubmitting = true;

    const payload = {
      se_identifier: this.exerciceSelectionne,
      title: this.newMouvement.title,
      amount: this.newMouvement.amount,
      date: this.newMouvement.date,
      type_charges: this.activeTab, 
      sub_type_charges: this.newMouvement.sub_type_charges,
      owner_id: this.newMouvement.type_mouvement === 'encaissement' ? this.newMouvement.owner_id : null,
      cle_repartition_id: this.newMouvement.type_mouvement === 'depense' ? this.newMouvement.cle_repartition_id : null,
    };

    const requeteApi = this.newMouvement.type_mouvement === 'encaissement' 
      ? this.budgetService.ajouterEncaissement(payload)
      : this.budgetService.ajouterDepense(payload);

    requeteApi.subscribe({
      next: (res: any) => {
        if (res.success) {
          this.closeAddModal();
          this.showToast("Opération enregistrée avec succès.", "success");
          this.chargerDonnees(true); 
        } else {
          // 🟢 هاهو الكود فين كيقرا الميساج من الباكاند ويطلعو فـ Pop-up يلا كان success: false
          this.showToast(res.message || "Opération refusée.", "error");
        }
        this.isSubmitting = false;
        this.cdr.detectChanges();
      },
      error: (err: any) => {
        // 🟢 هاهو الكود باش يقرأ اليرور يلا كان جايب معاه 403 أو 500
        const errorMessage = err.error?.message || err.message || "Une erreur est survenue.";
        this.showToast(errorMessage, "error");
        this.isSubmitting = false;
        this.cdr.detectChanges();
      }
    });
  }

  telechargerReleve() {
    if (!this.exerciceSelectionne) {
      this.showToast("Veuillez sélectionner un exercice d'abord.", "error");
      return;
    }

    const payload = {
      exercise: this.exerciceSelectionne,
      type: this.activeTab
    };

    this.budgetService.telechargerReleve(payload).subscribe({
      next: (blob: Blob) => {
        const url = window.URL.createObjectURL(blob);
        const a = document.createElement('a');
        a.href = url;
        a.download = `Releve_${this.activeTab}_${this.exerciceSelectionne}.pdf`;
        document.body.appendChild(a);
        a.click(); 
        setTimeout(() => {
            window.URL.revokeObjectURL(url);
            a.remove();
        }, 2000);
      },
      error: (err) => {
        console.error("❌ Erreur lors du téléchargement", err);
        this.showToast("Erreur lors du téléchargement du relevé.", "error");
      }
    });
  }

  toggleMenu(operationActuelle: any) {
    this.operations.forEach(op => {
      if (op !== operationActuelle) {
        op.showMenu = false;
      }
    });
    operationActuelle.showMenu = !operationActuelle.showMenu;
  }

  modifierOperation(op: any) {
    op.showMenu = false; 
    this.editMouvement = { ...op };
    this.editMouvement.montant_absolu = Math.abs(op.montant); 
    this.isEditModalOpen = true; 
  }

  closeEditModal() {
    this.isEditModalOpen = false;
    this.editMouvement = {};
  }

  soumettreModification() {
    if (!this.editMouvement.libelle || !this.editMouvement.montant_absolu || !this.editMouvement.date) {
      this.showToast("Veuillez remplir tous les champs obligatoires.", "error");
      return;
    }

    this.isSubmitting = true;

    const payload = {
      se_identifier: this.exerciceSelectionne,
      origin_id: this.editMouvement.origin_id,
      title: this.editMouvement.libelle,
      amount: this.editMouvement.montant_absolu,
      date: this.editMouvement.date,
      sub_type_charges: this.editMouvement.sub_type_charges
    };

    setTimeout(() => {
      this.isSubmitting = false;
      this.closeEditModal();
      this.showToast("Opération modifiée avec succès.", "success");
      this.chargerDonnees(true);
    }, 800);
  }

  supprimerOperation(op: any) {
    op.showMenu = false; 
    this.operationToDelete = op;
    this.isConfirmModalOpen = true;
    this.cdr.detectChanges();
  }

  fermerConfirmModal() {
    this.isConfirmModalOpen = false;
    this.operationToDelete = null;
    this.cdr.detectChanges();
  }

  confirmerSuppression() {
    if (!this.operationToDelete) return;
    
    const op = this.operationToDelete;
    this.isConfirmModalOpen = false; 
    
    const payload: any = {
      se_identifier: this.exerciceSelectionne
    };

    if (op.type === 'Encaissement') {
      payload.sen_identifier = op.origin_id;
    } else {
      payload.sdep_identifier = op.origin_id;
    }

    const tab = this.activeTab;
    this.operationsParTab[tab] = this.operationsParTab[tab].filter(
      o => o.origin_id !== op.origin_id
    );
    
    this.showToast("Opération supprimée avec succès.", "success");
    this.operationToDelete = null;
    this.cdr.detectChanges();
  }
}