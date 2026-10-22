import { Component, OnInit, ChangeDetectorRef } from '@angular/core';
import { CommonModule } from '@angular/common';
import { FormsModule } from '@angular/forms';
import { PageHeader } from '../../components/page-header/page-header';
import { HttpClient, HttpHeaders } from '@angular/common/http'; 

@Component({
  selector: 'app-simulation-budget',
  standalone: true,
  imports: [CommonModule, FormsModule, PageHeader],
  templateUrl: './simulerbudget.html'
})
export class SimulationBudget implements OnInit {
  
  proprieteId: string = '';
  montantSaisi: number | null = null;
  cleSelectionnee: string = ''; 
  activeTab: 'coproprietaire' | 'lot' = 'coproprietaire';
  isCleMenuOpen: boolean = false;

  isLoading: boolean = false;
  listeCles: any[] = []; 
  resultatsSimulation: any[] = [];
  residenceInfo = { nom: '...', adresse: '...' };

  // 🟢 VARIABLES DYAL L-POP-UP (TOAST)
  showNotification: boolean = false;
  notificationMessage: string = '';
  notificationType: 'success' | 'error' = 'success';
  notificationTimeout: any;

  constructor(
    private http: HttpClient,
    private cdr: ChangeDetectorRef
  ) {}

  ngOnInit() {
    this.chargerDonneesSimulation();
  }

  // 🟢 Helper باش نجبدو الـ ID من LocalStorage
  private getActivePropId() {
    return localStorage.getItem('active_propriete_id') || '';
  }

  // 🟢 Helper باش نصيفطو الـ Headers
  private getHeaders() {
    return new HttpHeaders({
      'residence_id': this.getActivePropId()
    });
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

  chargerDonneesSimulation() {
    this.isLoading = true;
    const apiUrl = `http://51.178.87.234 :8021/api/simulation/charger`; 
    
    // 🟢 زدنا residence_id فـ Payload والـ Headers
    this.http.post(apiUrl, { residence_id: this.getActivePropId() }, {
      headers: this.getHeaders(),
      withCredentials: true
    }).subscribe({
      next: (res: any) => {
        if (res.success) {
          if (res.residence) {
            this.residenceInfo = res.residence;
          }
          this.listeCles = res.data || [];
          if (this.listeCles.length > 0) {
            this.cleSelectionnee = this.listeCles[0].id;
            this.calculerResultats(0); 
          } else {
            this.resultatsSimulation = [];
          }
        } else {
          this.listeCles = [];
          this.resultatsSimulation = [];
        }
        this.isLoading = false;
        this.cdr.detectChanges();
      },
      error: (err) => {
        console.error('❌ Erreur API Simulation:', err);
        this.listeCles = [];
        this.resultatsSimulation = [];
        this.isLoading = false;
        this.cdr.detectChanges();
      }
    });
  }

  onCleChange(cleId: string) {
    this.cleSelectionnee = cleId;
    this.isCleMenuOpen = false;
    this.genererSimulation(); 
  }

  setTab(tab: 'coproprietaire' | 'lot') {
    this.activeTab = tab;
    this.genererSimulation(); 
  }

  genererSimulation() {
    if (!this.cleSelectionnee) {
        this.showToast("Veuillez sélectionner une clé de répartition.", "error");
        return;
    }
    const montant = this.montantSaisi ? Number(this.montantSaisi) : 0;
    this.calculerResultats(montant);
  }

  calculerResultats(montant: number) {
    this.resultatsSimulation = [];
    const cleActuelle = this.listeCles.find(c => c.id == this.cleSelectionnee);
    
    if (!cleActuelle || !cleActuelle.lots || cleActuelle.lots.length === 0) {
        this.cdr.detectChanges();
        return;
    }

    if (this.activeTab === 'lot') {
        this.resultatsSimulation = cleActuelle.lots.map((lot: any) => {
            const t = Number(lot.tantieme) || 0;
            const tTotal = Number(lot.tantiemes_total) || 0;
            const fraction = tTotal > 0 ? (t / tTotal) : 0;
            const part = Number((montant * fraction).toFixed(2));
            const nomCompletBien = `${lot.type || 'Bien'} ${lot.numero_porte || lot.lot_id}`;

            return {
                identifiant: nomCompletBien,
                // 🟢 FIX HNA: Kanderu toFixed(2) bash n-biyynou ghir 2 arqam wra l-fasila
                tantieme: t.toFixed(2), 
                budget: part,
                total: part
            };
        });
    } else if (this.activeTab === 'coproprietaire') {
        const mapProprietaires = new Map<string, any>();
        cleActuelle.lots.forEach((lot: any) => {
            const t = Number(lot.tantieme) || 0;
            const tTotal = Number(lot.tantiemes_total) || 0;
            const fraction = tTotal > 0 ? (t / tTotal) : 0;
            const part = Number((montant * fraction).toFixed(2));
            const ownerId = lot.owner_id ? lot.owner_id.toString() : `sans-proprio-${lot.lot_id}`;
            const ownerName = lot.owner_name || 'Sans propriétaire';

            if (mapProprietaires.has(ownerId)) {
                const existant = mapProprietaires.get(ownerId);
                existant.budget += part;
                existant.total = existant.budget; 
                // 🟢 FIX HNA: Kan-jm3ou l-arqam l-asliyin f _rawTantieme w n-formatiwha f tantieme
                existant._rawTantieme += t; 
                existant.tantieme = existant._rawTantieme.toFixed(2); 
            } else {
                mapProprietaires.set(ownerId, {
                    nom: ownerName,
                    _rawTantieme: t, // 🟢 FIX HNA
                    tantieme: t.toFixed(2), // 🟢 FIX HNA
                    budget: part,
                    total: part 
                });
            }
        });
        this.resultatsSimulation = Array.from(mapProprietaires.values());
    }
    this.cdr.detectChanges();
  }

  formatLotId(id: any): string {
    if (!id) return '';
    const cleanId = String(id).replace(/\D/g, ''); 
    if (!cleanId) return String(id); 
    const visualId = Number(cleanId) + 845752; 
    return 'LOT-' + visualId.toString().padStart(8, '0');
  }
}