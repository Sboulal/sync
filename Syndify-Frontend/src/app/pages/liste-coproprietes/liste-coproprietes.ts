import { Component, OnInit, ChangeDetectorRef, signal, WritableSignal } from '@angular/core'; 
import { CommonModule } from '@angular/common';
import { ActivatedRoute, Router } from '@angular/router';
import { PageHeader } from '../../components/page-header/page-header';
import { FormsModule } from '@angular/forms'; 

// 🟢 استيراد الـ SDK بلاصة لي Services القدام
import { IdpReqService } from 'idp-com-sdk';

@Component({
  selector: 'app-liste-coproprietaires',
  standalone: true,
  imports: [CommonModule, PageHeader, FormsModule], 
  templateUrl: './liste-coproprietes.html',
})
export class ListeCoproprietes implements OnInit {
  
  currentStatus: 'actif' | 'inactif' | 'en_attente' = 'actif';
  totalAffichage: number = 0;

  residenceInfo = { nom: '...', adresse: '...' };
  
  coproprietaires: any[] = [];
  lastId: number | null = null;
  isThereMore: boolean = false;
  isLoading: boolean = false;

  tousLesLots: any[] = [];

  isAddModalOpen: boolean = false;
  isAdding: boolean = false;
  
  newCopro: any = {
    user_id: '',
    nom: '',
    email: '',
    tel: '',
    selectedLots: [], 
    status: 'Actif'
  };

  isLotsModalOpen: boolean = false;
  selectedCoproName: string = '';
  selectedCoproLots: any[] = [];
  activeDropdown: string | null = null;

  showNotification: boolean = false;
  notificationMessage: string = '';
  notificationType: 'success' | 'error' = 'success';
  notificationTimeout: any;

  isDeleteModalOpen: boolean = false;
  coproToDeleteId: string | null = null;

  // 🟢 Signal ديال الـ Gateway
  actionSignal: WritableSignal<boolean> = signal(false);

  constructor(
    private idpreqservice: IdpReqService, // 🟢 الـ SDK
    private route: ActivatedRoute,
    private router: Router,
    private cdr: ChangeDetectorRef 
  ) {}

  ngOnInit() {
    this.chargerListe(true); 
    this.chargerLots(); 
  }

  // 🟢 Helper باش نجبدو ID الإقامة
  private getActivePropId() {
    return localStorage.getItem('active_propriete_id') || '';
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

  chargerLots() {
    const payload = { residence_id: this.getActivePropId() };

    this.idpreqservice.forwardRequest(
      'api/lots/liste',
      'post',
      payload,
      this.actionSignal,
      (res: any) => {
        if (res.success) {
          this.tousLesLots = res.data || [];
        }
      },
      (err: any) => { console.error('Erreur chargement lots', err); }
    );
  }

  getSelectedLotObjects(): any[] {
    if (!this.newCopro.selectedLots || this.newCopro.selectedLots.length === 0) return [];
    return this.tousLesLots.filter(l => this.newCopro.selectedLots.includes(l.id));
  }

  changerTab(status: 'actif' | 'inactif' | 'en_attente') {
    if (this.currentStatus !== status) {
      this.currentStatus = status;
      this.chargerListe(true);
    }
  }

  chargerListe(reset: boolean = false) {
    if (this.isLoading) return; 

    if (reset) {
      this.coproprietaires = [];
      this.lastId = null;
    }

    this.isLoading = true;
    this.cdr.detectChanges(); 

    const payload: any = {
      residence_id: this.getActivePropId(),
      type_affichage: this.currentStatus,
      type: this.currentStatus // كنصيفطوهم بجوج احتياط
    };

    if (this.lastId) payload.last_id = this.lastId;

    this.idpreqservice.forwardRequest(
      'api/coproprietaires/liste',
      'post',
      payload,
      this.actionSignal,
      (res: any) => {
        if (res.success) {
          this.coproprietaires = [...this.coproprietaires, ...(res.data || [])];
          
          if (res.residence) {
            this.residenceInfo = res.residence;
          }

          this.isThereMore = res.is_there_more;
          
          if (res.data && res.data.length > 0) {
            this.lastId = res.data[res.data.length - 1].user_id;
          }
        }
        this.isLoading = false;
        this.cdr.detectChanges();
      },
      (err: any) => {
        this.isLoading = false;
        this.showToast("Erreur lors du chargement.", 'error');
        this.cdr.detectChanges();
      }
    );
  }

  supprimerCopro(userId: string) {
    this.closeDropdown(); 
    this.coproToDeleteId = userId;
    this.isDeleteModalOpen = true;
    this.cdr.detectChanges();
  }

  closeDeleteModal() {
    this.isDeleteModalOpen = false;
    this.coproToDeleteId = null;
    this.cdr.detectChanges();
  }

  confirmerSuppression() {
    if (!this.coproToDeleteId) return;

    const payload = {
      user_id: this.coproToDeleteId,
      residence_id: this.getActivePropId()
    };

    this.idpreqservice.forwardRequest(
      'api/coproprietaires/supprimer',
      'post',
      payload,
      this.actionSignal,
      (res: any) => {
        if (res.success) {
          this.chargerListe(true); 
          this.showToast("Copropriétaire supprimé avec succès.", 'success');
          this.closeDeleteModal();
        }
      },
      (err: any) => {
        this.showToast(err.error?.message || 'Erreur lors de la suppression', 'error');
        this.closeDeleteModal();
      }
    );
  }

  voirHistorique(userId: string) {
    this.router.navigate(['/coproprietaires/historique', userId]); 
  }

  voirListeLots(copro: any, event: Event) {
    event.stopPropagation(); 
    this.closeDropdown();
    
    this.selectedCoproName = copro.nom;
    
    if (copro.lot_ids && copro.lot_ids.length > 0) {
      this.selectedCoproLots = this.tousLesLots.filter(l => copro.lot_ids.includes(l.id));
    } else {
      this.selectedCoproLots = [];
    }
    
    this.isLotsModalOpen = true;
    this.cdr.detectChanges();
  }

  toggleDropdown(userId: string, event: Event) {
    event.stopPropagation();
    this.activeDropdown = this.activeDropdown === userId ? null : userId;
    this.cdr.detectChanges();
  }

  closeDropdown() {
    this.activeDropdown = null;
    this.cdr.detectChanges();
  }

  openEditModal(copro: any, event?: Event) {
    if (event) event.stopPropagation(); 
    this.closeDropdown(); 
    this.newCopro = { 
      user_id: copro.user_id, 
      nom: copro.nom, 
      email: copro.email, 
      tel: copro.tel,
      selectedLots: copro.lot_ids ? [...copro.lot_ids] : [],
      status: copro.status 
    };
    this.isAddModalOpen = true;
    this.cdr.detectChanges();
  }

  openAddModal() {
    this.newCopro = { 
      user_id: '', 
      nom: '', 
      email: '', 
      tel: '',
      selectedLots: [], 
      status: 'Actif' 
    };
    
    this.isAddModalOpen = true;
    this.cdr.detectChanges();
  }

  formatCoproId(id: any): string {
    if (!id) return '';
    const idStr = String(id);
    if (idStr.includes('-')) {
        return 'COP-' + idStr.replace(/-/g, '').slice(-8).toUpperCase();
    }
    const visualId = Number(id) + 845752;
    return 'COP-' + visualId.toString().padStart(8, '0');
  }

  closeAddModal() {
    this.isAddModalOpen = false;
    this.cdr.detectChanges();
  }

  closeLotsModal() {
    this.isLotsModalOpen = false;
    this.selectedCoproLots = [];
    this.cdr.detectChanges();
  }

  ajouterCopro() {
    if (!this.newCopro.email || !this.newCopro.nom) return;

    this.isAdding = true;
    this.cdr.detectChanges();

    const payload = {
      nom: this.newCopro.nom,
      email: this.newCopro.email,
      tel: this.newCopro.tel,
      status: this.newCopro.status,
      selectedLots: this.newCopro.selectedLots,
      user_id: this.newCopro.user_id, 
      owner_id: this.newCopro.user_id,
      residence_id: this.getActivePropId()
    };

    this.idpreqservice.forwardRequest(
      'api/coproprietaires/ajouter',
      'post',
      payload,
      this.actionSignal,
      (res: any) => {
        if (res.success) {
          this.closeAddModal();
          this.chargerListe(true); 
          this.showToast(res.message || "Opération réussie !", 'success'); 
        }
        this.isAdding = false;
        this.cdr.detectChanges();
      },
      (err: any) => {
        this.showToast(err.error?.message || "Erreur lors de l'ajout.", 'error'); 
        this.isAdding = false;
        this.cdr.detectChanges();
      }
    );
  }

  // 🟢 الدالة الجديدة باش السانديك يأكتيفي الساكن بضغطة وحدة
  activerCopro(copro: any, event: Event) {
    event.stopPropagation();
    this.closeDropdown();
    this.isLoading = true;
    this.cdr.detectChanges();

    const payload = {
      nom: copro.nom,
      email: copro.email,
      tel: copro.tel,
      status: 'Actif', 
      selectedLots: copro.lot_ids ? [...copro.lot_ids] : [],
      user_id: copro.user_id,
      owner_id: copro.user_id,
      residence_id: this.getActivePropId()
    };

    this.idpreqservice.forwardRequest(
      'api/coproprietaires/ajouter',
      'post',
      payload,
      this.actionSignal,
      (res: any) => {
        if (res.success) {
          this.chargerListe(true); 
          this.showToast("Copropriétaire activé avec succès !", 'success');
        } else {
          this.isLoading = false;
          this.cdr.detectChanges();
        }
      },
      (err: any) => {
        this.showToast(err.error?.message || "Erreur lors de l'activation.", 'error');
        this.isLoading = false;
        this.cdr.detectChanges();
      }
    );
  }
}