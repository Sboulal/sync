import { Component, OnInit, ChangeDetectorRef, signal, WritableSignal } from '@angular/core';
import { CommonModule } from '@angular/common';
import { HttpClient } from '@angular/common/http';
import { Router } from '@angular/router';
import { FormsModule } from '@angular/forms';
// 🟢 Import dyal l'SDK li 3tak Yassine
import { IdpReqService } from 'idp-com-sdk'; 

interface Syndic {
  id: number;
  nom: string;
  email: string;
  tel: string | null;
  estVous: boolean;
}

interface CoproprietaireInfo {
  id: number;
  nom: string;
  email: string;
  tel: string | null;
  estVous: boolean;
}

interface Copropriete {
  id: string;
  nom: string;
  siret: string;
  dateAjout: string;
  localisation: string;
  gerePar: string;
  syndics: Syndic[];
  nbSyndics: number;
  role: string;
  status: 'active' | 'en_attente' | 'inactif' | 'sans_acces';
  hasAccess: boolean;
  nbCoproprietaires: number;
  coproprietaires_list: CoproprietaireInfo[];
  nbLots: number;
}

interface CurrentUser {
  nom: string;
  date: string;
  derniereActivite: string;
  roles: string;
  proprietesActives: string;
  abonnement: string;
  version: string;
}

@Component({
  selector: 'app-home',
  standalone: true,
  imports: [CommonModule, FormsModule],
  templateUrl: './home.html',
})
export class Home implements OnInit {

  isLoading: boolean = true;
  activeDropdown: any = null;
  isProfileDropdownOpen: boolean = false; 

  activeModal: 'modifier' | 'transferer' | 'supprimer' | null = null;
  selectedProp: Copropriete | null = null;
  editData = { nom: '', siret: '', adresse: '', ville: '' };
  transferEmail: string = '';

  currentUser: CurrentUser = {
    nom: '...', date: '...', derniereActivite: '...', roles: '...', proprietesActives: '...', abonnement: '...', version: '...'
  };

  coproprietes: Copropriete[] = [];
  
  // 🟢 التغيير هنا: حيدنا الليان الطويل وخلينا غير 'api'
  private baseUrl = 'api';

  // 🟢 Signal li mtlob f l'SDK bach y-gérer loading state dyal l'Gateway
  actionSignal: WritableSignal<boolean> = signal(false);

  constructor(
    private http: HttpClient,
    private router: Router,
    private cdr: ChangeDetectorRef,
    private idpreqservice: IdpReqService // 🟢 Injection dyal l'SDK
  ) {}

  ngOnInit(): void {
    this.chargerDonnees();
  }

  // ==========================================
  // GESTION DU DROPDOWN (PROPRIÉTÉS)
  // ==========================================
  toggleDropdown(prop: Copropriete, event: Event) {
    event.preventDefault();
    event.stopPropagation();
    this.activeDropdown = this.activeDropdown === prop.id ? null : prop.id;
    this.cdr.detectChanges();
  }

  closeDropdown() {
    this.activeDropdown = null;
    this.cdr.detectChanges();
  }

  naviguerVersAjout() {
    this.router.navigate(['/nouvelle-copropriete']);
  }

  // ==========================================
  // GESTION DES MODALS
  // ==========================================
  openModal(type: 'modifier' | 'transferer' | 'supprimer', prop: any, event: Event) {
    event.stopPropagation();
    this.closeDropdown();
    this.selectedProp = prop;
    this.activeModal = type;

    if (type === 'modifier') {
      this.editData = { 
        nom: prop.nom, 
        siret: prop.siret,
        adresse: prop.adresse || '',
        ville: prop.ville_exacte || ''
      };
    }
  }
  
  closeModal() {
    this.activeModal = null;
    this.selectedProp = null;
    this.transferEmail = '';
  }

  validerModification() {
    if (!this.selectedProp) return;

    const payload = {
      nom: this.editData.nom,
      adresse: this.editData.adresse,
      ville: this.editData.ville
    };

    // 🟢 SDK Modification: غتولي دابا api/proprietes/{id}/modifier
    this.idpreqservice.forwardRequest(
      `${this.baseUrl}/proprietes/${this.selectedProp.id}/modifier`, 
      'post', 
      payload, 
      this.actionSignal,
      (res: any) => {
        const resData = res.data || res;
        if (resData.success) {
          if (this.selectedProp) {
              this.selectedProp.nom = this.editData.nom;
          }
          console.log("✅ Copropriété mise à jour avec succès !");
          this.closeModal();
        } else {
          alert("Erreur: " + resData.message);
        }
      },
      (err: any) => {
        console.error("❌ Erreur lors de la modification:", err);
        alert("Une erreur s'est produite lors de la sauvegarde.");
      }
    );
  }

  validerTransfert() {
    console.log("Transfert de", this.selectedProp?.nom, "vers", this.transferEmail);
    this.closeModal();
  }

  confirmerSuppression() {
    if (!this.selectedProp) return;

    const propId = this.selectedProp.id;
    console.log("Suppression en cours de la copropriété ID:", propId);

    // 🟢 SDK Suppression: غتولي دابا api/proprietes/{id}/supprimer
    this.idpreqservice.forwardRequest(
      `${this.baseUrl}/proprietes/${propId}/supprimer`, 
      'delete', 
      {}, 
      this.actionSignal,
      (res: any) => {
        const resData = res.data || res;
        if (resData.success) {
          console.log("✅ Copropriété supprimée avec succès !");
          this.coproprietes = this.coproprietes.filter(c => c.id !== propId);
          
          const currentResidenceId = localStorage.getItem('active_propriete_id');
          if (currentResidenceId === propId) {
              localStorage.removeItem('active_propriete_id');
              localStorage.removeItem('residence_nom');
          }

          this.closeModal();
          this.cdr.detectChanges(); 
        } else {
          alert("Erreur: " + resData.message);
          this.closeModal();
        }
      },
      (err: any) => {
        console.error("❌ Erreur lors de la suppression:", err);
        alert("Une erreur s'est produite lors de la suppression.");
        this.closeModal();
      }
    );
  }

  chargerDonnees() {
    this.isLoading = true;
    
    // 🟢 SDK Chargement Home: غتولي دابا api/home-data
    this.idpreqservice.forwardRequest(
      `${this.baseUrl}/home-data`, 
      'post', 
      {}, 
      this.actionSignal,
      (res: any) => {
        const resData = res.data || res;
        if (resData.success) {
          this.currentUser = resData.currentUser;
          this.coproprietes = resData.coproprietes;
        }
        this.isLoading = false;
        this.cdr.detectChanges();
      },
      (err: any) => {
        console.error('❌ Erreur chargement home:', err);
        this.isLoading = false;
        this.cdr.detectChanges();
      }
    );
  }

  getSyndicsDisplay(prop: Copropriete, limit: number = 2): string {
    if (!prop.syndics || prop.syndics.length === 0) return '---';
    const noms = prop.syndics.map(s => s.estVous ? `${s.nom} (vous)` : s.nom);
    if (noms.length <= limit) return noms.join(', ');
    const reste = noms.length - limit;
    return `${noms.slice(0, limit).join(', ')} +${reste} autre${reste > 1 ? 's' : ''}`;
  }

  getCoprosDisplay(prop: Copropriete, limit: number = 2): string {
    if (!prop.coproprietaires_list || prop.coproprietaires_list.length === 0) return '---';
    const noms = prop.coproprietaires_list.map(c => c.estVous ? `${c.nom} (vous)` : c.nom);
    if (noms.length <= limit) return noms.join(', ');
    const reste = noms.length - limit;
    return `${noms.slice(0, limit).join(', ')} +${reste} autre${reste > 1 ? 's' : ''}`;
  }

  accederResidence(prop: Copropriete, event?: Event) {
    if (event) event.stopPropagation();
    this.closeDropdown();

    if (!prop.hasAccess) {
        alert("🔒 Vous n'avez pas accès à cette copropriété.");
        return;
    }
    if (prop.status === 'en_attente') {
        alert("⏳ Votre demande est en cours de traitement par le syndic.");
        return;
    }

    localStorage.setItem('active_propriete_id', prop.id);
    localStorage.setItem('residence_nom', prop.nom);

    this.router.navigate(['/dashboard'], { queryParams: { residence_id: prop.id } });
  }

  // ==========================================
  // LOGOUT
  // ==========================================
  Logout() {
    // 🟢 SDK Logout: غتولي دابا api/logout
    this.idpreqservice.forwardRequest(
      `${this.baseUrl}/logout`, 
      'post', 
      {}, 
      this.actionSignal,
      (res: any) => {
        this.clearSessionAndRedirect();
      },
      (err: any) => {
        console.error('Erreur lors de la requête de déconnexion :', err);
        this.clearSessionAndRedirect();
      }
    );   
  }

  clearSessionAndRedirect() {
    localStorage.removeItem('is_logged_in'); 
    localStorage.removeItem('user_id');
    localStorage.removeItem('user_role');
    localStorage.removeItem('active_propriete_id');
    localStorage.removeItem('residence_nom');
    localStorage.removeItem('access_token'); 
    
    this.router.navigate(['/login']);
  }
}