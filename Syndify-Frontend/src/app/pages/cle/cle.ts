import { Component, OnInit, ChangeDetectorRef } from '@angular/core';
import { CommonModule } from '@angular/common';
import { PageHeader } from '../../components/page-header/page-header';
import { FormsModule } from '@angular/forms';
import { CleRepartitionService } from '../../services/cle-repartition'; 
import { LotService } from '../../services/lot'; 
import { forkJoin } from 'rxjs';

@Component({
  selector: 'app-cle',
  standalone: true,
  imports: [CommonModule, PageHeader, FormsModule], 
  templateUrl: './cle.html',
})
export class Cle implements OnInit {
  
  currentTab: 'lots' | 'coproprietaires' = 'lots';
  isLoading: boolean = false;
  activeDropdown: string | number | null = null;
  
  clesList: any[] = []; 
  lignesTableau: any[] = []; 
  lignesCopro: any[] = [];
  residenceInfo = { nom: '...', adresse: '...' };
  
  isAddModalOpen: boolean = false;
  isSaving: boolean = false;
  tousLesLots: any[] = []; 
  
  cleForm: any = {
    id: null,
    nom_cle: '',
    tantiemes_total: null,
    notes: '',
    unites: [] 
  };

  isDeleteModalOpen: boolean = false;
  cleToDelete: number | null = null;

  showNotification: boolean = false;
  notificationMessage: string = '';
  notificationType: 'success' | 'error' = 'success';
  notificationTimeout: any;

  parseFloat = parseFloat;

  constructor(
    private cleService: CleRepartitionService,
    private lotService: LotService,
    private cdr: ChangeDetectorRef
  ) {}

  ngOnInit() {
    this.chargerDonnees();
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

  changerTab(tab: 'lots' | 'coproprietaires') {
    this.currentTab = tab;
  }

  chargerDonnees() {
    this.isLoading = true;

    forkJoin({
      lots: this.lotService.getListe(),
      cles: this.cleService.getListe()
    }).subscribe({
      next: (res: any) => {
        if (res.lots.success) {
          this.tousLesLots = res.lots.data || [];
          if (res.lots.residence) {
            this.residenceInfo = res.lots.residence;
          }
        }

        if (res.cles.success) {
          this.clesList = res.cles.data || [];
          if (res.cles.residence) {
            this.residenceInfo = res.cles.residence;
          }
        } else {
          this.clesList = [];
        }

        this.formaterTableau();
        this.isLoading = false;
        this.cdr.detectChanges();
      },
      error: (err: any) => {
        console.error('Erreur chargement données:', err);
        this.clesList = [];
        this.formaterTableau();
        this.isLoading = false;
        this.cdr.detectChanges();
      }
    });
  }

  formaterTableau() {
    const lotsMap = new Map();

    this.tousLesLots.forEach(lot => {
      // 🔧 FIX: skip lots sans owner (pas de coproprietaire affecté)
      const hasOwner = lot.owners && lot.owners.length > 0;
      if (!hasOwner) return;

      const coproprietaire = lot.owners[0].nom || lot.owners[0].full_name || null;
      
      // 🔧 FIX: skip si le nom est vide/null aussi
      if (!coproprietaire) return;

      const bienName = this.formatBienName(lot);

      lotsMap.set(lot.id, {
        id: lot.id,
        details: bienName,
        coproprietaire,
        tantiemes: {}
      });
    });

    this.clesList.forEach(cle => {
      if (cle.lots && Array.isArray(cle.lots)) {
        cle.lots.forEach((lot: any) => {
          if (lotsMap.has(lot.id)) {
            lotsMap.get(lot.id).tantiemes[cle.id] = lot.tantieme_applied || 0;
          }
        });
      }
    });

    this.lignesTableau = Array.from(lotsMap.values());

    // Tab coproprietaires: grouper par coproprietaire
    const coproMap = new Map();
    this.lignesTableau.forEach(row => {
      // 🔧 FIX: skip rows sans coproprietaire valide
      if (!row.coproprietaire) return;

      const key = row.coproprietaire;
      if (!coproMap.has(key)) {
        coproMap.set(key, {
          coproprietaire: key,
          nb_biens: 0,
          biens_noms: [],
          tantiemes: {}
        });
      }
      const copro = coproMap.get(key);
      copro.nb_biens += 1;
      copro.biens_noms.push(row.details);

      this.clesList.forEach(cle => {
        copro.tantiemes[cle.id] = (copro.tantiemes[cle.id] || 0) + (row.tantiemes[cle.id] || 0);
      });
    });

    this.lignesCopro = Array.from(coproMap.values());
  }

  openAddModal(cleAModifier: any = null) {
    this.closeDropdown();

    if (cleAModifier) {
      const unitesFormatees = this.tousLesLots.map(lot => {
        const lotTrouve = cleAModifier.lots.find((l: any) => l.id === lot.id);
        return {
          id_unite: lot.id,
          numero_porte: lot.numero_porte,
          type: lot.type,        
          batiment: lot.batiment, 
          etage: lot.etage,       
          tantieme_applique: lotTrouve ? parseFloat(lotTrouve.tantieme_applied) : 0 
        };
      });

      this.cleForm = {
        id: cleAModifier.id,
        nom_cle: cleAModifier.nom,
        tantiemes_total: parseFloat(cleAModifier.tantiemes_total),
        notes: cleAModifier.notes,
        unites: unitesFormatees
      };
    } else {
      this.cleForm = {
        id: null,
        nom_cle: '',
        tantiemes_total: 10000, 
        notes: '',
        unites: this.tousLesLots.map(l => ({
          id_unite: l.id,
          numero_porte: l.numero_porte, 
          type: l.type,        
          batiment: l.batiment, 
          etage: l.etage,       
          tantieme_applique: 0
        }))
      };
    }
    
    this.isAddModalOpen = true;
  }

  closeAddModal() {
    this.isAddModalOpen = false;
  }

  formatBienName(lot: any): string {
    const type = lot.type || 'Bien';
    const numero = lot.numero_porte || '00';
    return `${type} ${numero}`;
  }

  enregistrerCle() {
    const totalSaisi = this.getSommeTantiemes();
    const totalAttendu = parseFloat(Number(this.cleForm.tantiemes_total).toFixed(4));

    if (totalSaisi !== totalAttendu) {
      this.showToast(`La somme des tantièmes (${totalSaisi}) ne correspond pas au total (${totalAttendu}).`, "error");
      return;
    }

    this.isSaving = true;
    
    const payload: any = {
      nom_cle: this.cleForm.nom_cle,
      tantiemes_total: totalAttendu,
      notes: this.cleForm.notes,
      unites: this.cleForm.unites
    };

    if (this.cleForm.id) {
      payload.scr_identifier = this.cleForm.id;
    }

    const action = this.cleForm.id 
      ? this.cleService.modifier(payload) 
      : this.cleService.ajouter(payload);

    action.subscribe({
      next: (res: any) => {
        if (res.success) {
          this.showToast(this.cleForm.id ? "Clé modifiée avec succès." : "Clé ajoutée avec succès.", "success");
          this.closeAddModal();
          this.chargerDonnees();
        }
        this.isSaving = false;
      },
      error: (err: any) => {
        this.showToast(err.error?.message || "Une erreur s'est produite.", "error");
        this.isSaving = false;
      }
    });
  }

  getSommeTantiemes(): number {
    if (!this.cleForm.unites) return 0;
    const sum = this.cleForm.unites.reduce((acc: number, u: any) => acc + (parseFloat(u.tantieme_applique) || 0), 0);
    return parseFloat(sum.toFixed(4)); 
  }

  formatLotId(id: any): string {
    if (!id) return '';
    const visualId = Number(id) + 845752; 
    return 'BN-' + visualId.toString().padStart(8, '0');
  }

  supprimerCle(id: number) {
    this.closeDropdown();
    this.cleToDelete = id;
    this.isDeleteModalOpen = true;
  }

  closeDeleteModal() {
    this.isDeleteModalOpen = false;
    this.cleToDelete = null;
  }

  confirmerSuppression() {
    if (!this.cleToDelete) return;

    this.cleService.supprimer(this.cleToDelete).subscribe({
      next: (res: any) => {
        if (res.success) {
          this.showToast("Clé supprimée avec succès.", "success");
          this.closeDeleteModal();
          this.chargerDonnees();
        }
      },
      error: (err: any) => {
        this.showToast("Erreur lors de la suppression.", "error");
      }
    });
  }

  toggleDropdown(cleId: string | number, event: Event) {
    event.stopPropagation();
    this.activeDropdown = this.activeDropdown === cleId ? null : cleId;
  }

  closeDropdown() {
    this.activeDropdown = null;
  }
}