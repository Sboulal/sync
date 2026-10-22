import { Component, OnInit, ChangeDetectorRef, signal, WritableSignal } from '@angular/core';
import { CommonModule } from '@angular/common';
import { ActivatedRoute } from '@angular/router'; 
import { PageHeader } from '../../components/page-header/page-header'; 
import { FormsModule } from '@angular/forms';

// 🟢 استيراد الـ SDK
import { IdpReqService } from 'idp-com-sdk';

@Component({
  selector: 'app-gestion-lots',
  standalone: true,
  imports: [CommonModule, PageHeader, FormsModule],
  templateUrl: './gestion-lots.html',
})
export class GestionLots implements OnInit {
  lots: any[] = []; 
  listCoproprietaires: any[] = []; 
  isLoading: boolean = false;
  residenceInfo = { nom: '...', adresse: '...' };

  isModalOpen: boolean = false;
  activeDropdown: number | null = null;
  
  lotForm: any = {
    id: null,
    random_ref: '',
    type: 'Appartement',
    batiment: '',
    etage: '',
    numero_porte: '',
    owner_id: null,
    owner_status: 'Actif'
  };

  isAddOwnerModalOpen: boolean = false;
  isAddingOwner: boolean = false;
  newOwner: any = {
    nom: '',
    email: '',
    tel: '',
    status: 'Actif'
  };

  showNotification: boolean = false;
  notificationMessage: string = '';
  notificationType: 'success' | 'error' = 'success';
  notificationTimeout: any;

  isDeleteModalOpen: boolean = false;
  lotToDelete: any = null;

  // 🟢 Signal ديال الـ Gateway
  actionSignal: WritableSignal<boolean> = signal(false);

  constructor(
    private idpreqservice: IdpReqService, // 🟢 الـ SDK
    private route: ActivatedRoute,
    private cdr: ChangeDetectorRef 
  ) {}

  ngOnInit() {
    console.log('🟢 [ngOnInit] Démarrage de Gestion des Lots...');
    this.chargerLots(); 
    // this.chargerUsers(); 
  }

  // 🟢 Helper باش نجبدو ID الإقامة
  private getActivePropId() {
    return localStorage.getItem('active_propriete_id') || '';
  }

chargerUsers() {
    const payload = { type: 'tous', residence_id: this.getActivePropId() };
    
    this.idpreqservice.forwardRequest(
      'api/coproprietaires/liste',
      'post',
      payload,
      this.actionSignal,
      (res: any) => {
        // 🟢 التعديل هنا: حيدنا resData وقرينا من res نيشان
        if (res.success && res.data) {
          this.listCoproprietaires = res.data.map((u: any) => ({
             ...u,
             user_id: u.user_id 
          }));
          this.cdr.detectChanges(); 
        }
      },
      (err: any) => { console.error('Erreur chargement users', err); }
    );
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

chargerLots() {
    this.isLoading = true;
    const payload = { residence_id: this.getActivePropId() };

    this.idpreqservice.forwardRequest(
      'api/lots/liste',
      'post',
      payload,
      this.actionSignal,
      (res: any) => {
        // 🟢 التعديل هنا: حيدنا الفخ ديال resData
        if (res.success) {
          this.lots = res.data || []; 
          if (res.residence) {
             this.residenceInfo = res.residence;
          }
        } else {
          this.lots = [];
        }
        this.isLoading = false;
        this.cdr.detectChanges();
      },
      (err: any) => {
        console.error("❌ Erreur chargement lots:", err);
        this.lots = []; 
        this.isLoading = false;
        this.cdr.detectChanges();
      }
    );
  }
  toggleDropdown(lotId: number, event: Event) {
    event.stopPropagation();
    this.activeDropdown = this.activeDropdown === lotId ? null : lotId;
    this.cdr.detectChanges(); 
  }

  closeDropdown() {
    if (this.activeDropdown !== null) {
      this.activeDropdown = null;
      this.cdr.detectChanges(); 
    }
  }

  openModal(lotAModifier: any = null) {
    this.activeDropdown = null;
    
    if (lotAModifier) {
        let currentOwnerId = null;
        if (lotAModifier.owners && lotAModifier.owners.length > 0) {
            currentOwnerId = lotAModifier.owners[0].user_id || lotAModifier.owners[0].id; 
        }

        this.lotForm = {
            id: lotAModifier.id,
            type: lotAModifier.type || 'Appartement',
            batiment: lotAModifier.batiment,
            etage: lotAModifier.etage,
            numero_porte: lotAModifier.numero_porte,
            owner_id: currentOwnerId, 
            owner_status: 'Actif'
        };
    } else {
        this.lotForm = { 
            id: null, type: 'Appartement', batiment: '', etage: '', numero_porte: '',
            owner_id: null, owner_status: 'Actif' 
        };
    }
    
    this.isModalOpen = true;
    this.cdr.detectChanges();
  }

  closeModal() {
    this.isModalOpen = false;
    this.cdr.detectChanges(); 
  }

  openAddOwnerModal() {
    this.newOwner = { nom: '', email: '', tel: '', status: 'Actif' };
    this.isAddOwnerModalOpen = true;
    this.cdr.detectChanges();
  }

  closeAddOwnerModal() {
    this.isAddOwnerModalOpen = false;
    this.cdr.detectChanges();
  }

  ajouterProprietaire() {
    if (!this.newOwner.nom || !this.newOwner.email) return;

    this.isAddingOwner = true;
    this.cdr.detectChanges();

    const payload = { ...this.newOwner, residence_id: this.getActivePropId() };

    this.idpreqservice.forwardRequest(
      'api/coproprietaires/ajouter',
      'post',
      payload,
      this.actionSignal,
      (res: any) => {
        const resData = res.data || res;
        if (resData.success) {
          this.chargerUsers(); // نعاودو نشارجيو اللائحة
          this.closeAddOwnerModal();
          this.showToast("Propriétaire ajouté et sélectionné !", 'success');
        }
        this.isAddingOwner = false;
        this.cdr.detectChanges();
      },
      (err: any) => {
        this.showToast(err.error?.message || "Erreur lors de l'ajout.", 'error');
        this.isAddingOwner = false;
        this.cdr.detectChanges();
      }
    );
  }

  enregistrerLot() {
    if (!this.lotForm.numero_porte) {
      this.showToast("Veuillez remplir le N° de porte.", "error");
      return;
    }

    const payload: any = {
      residence_id: this.getActivePropId(),
      type: this.lotForm.type,
      batiment: this.lotForm.batiment,
      etage: this.lotForm.etage,
      numero_porte: this.lotForm.numero_porte,
      owner_id: this.lotForm.owner_id, 
      owner_status: 'Actif',
      owners: this.lotForm.owner_id ? [{ owner_id: this.lotForm.owner_id, owner_status: 'Actif' }] : [],
    };

    let apiEndpoint = 'api/lots/ajouter';
    if (this.lotForm.id) {
        apiEndpoint = 'api/lots/modifier';
        payload.lot_id = this.lotForm.id;
    }

    this.idpreqservice.forwardRequest(
      apiEndpoint,
      'post',
      payload,
      this.actionSignal,
      (res: any) => {
        const resData = res.data || res;
        if (resData.success) {
          this.closeModal(); 
          this.chargerLots(); 
          this.showToast(resData.message || "Opération réussie !", 'success'); 
        }
      },
      (err: any) => {
        this.showToast(err.error?.message || "Une erreur est survenue.", 'error'); 
      }
    );
  }

  formatLotId(id: any): string {
    if (!id) return '';
    const numId = Number(id);
    if (isNaN(numId)) return 'BN-' + id; 
    return 'BN-' + (numId + 845752).toString().padStart(8, '0');
  }

  supprimerLot(lot: any) {
    this.closeDropdown();
    this.lotToDelete = lot;
    this.isDeleteModalOpen = true;
    this.cdr.detectChanges();
  }

  closeDeleteModal() {
    this.isDeleteModalOpen = false;
    this.lotToDelete = null;
    this.cdr.detectChanges();
  }

  confirmerSuppression() {
    if (!this.lotToDelete) return;
    
    const payload = { 
        lot_id: this.lotToDelete.id, 
        residence_id: this.getActivePropId() 
    };

    this.idpreqservice.forwardRequest(
      'api/lots/supprimer',
      'post',
      payload,
      this.actionSignal,
      (res: any) => {
        const resData = res.data || res;
        if (resData.success) {
          this.chargerLots(); 
          this.showToast("Lot supprimé avec succès.", 'success');
          this.closeDeleteModal();
        }
      },
      (err: any) => {
        this.showToast(err.error?.message || "Erreur lors de la suppression.", 'error');
        this.closeDeleteModal();
      }
    );
  }
}