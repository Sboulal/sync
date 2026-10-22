import { Component, OnInit, ChangeDetectorRef } from '@angular/core';
import { CommonModule } from '@angular/common';
import { HttpClient, HttpHeaders } from '@angular/common/http';
import { PageHeader } from '../../components/page-header/page-header';

@Component({
  selector: 'app-documents',
  standalone: true,
  imports: [CommonModule, PageHeader],
  templateUrl: './documents.html',
})

export class Documents implements OnInit {
  isLoading = false;
  residenceInfo = { nom: '...', adresse: 'Veuillez patienter' };
  
  totalSize: string = '0 B';
  totalSizeBytes: number = 0;
  storagePercentage: number = 0;
  
  recentFiles: any[] = [];
  folderContent: any[] = [];
  statistics: any[] = [];

  currentPath: string = ''; 
  pathHistory: string[] = []; 

  // 🟢 VARIABLES DYAL L-POP-UP (TOAST)
  showNotification: boolean = false;
  notificationMessage: string = '';
  notificationType: 'success' | 'error' = 'success';
  notificationTimeout: any;

  // 🟢 Liste dyal l-alwan
  barColors: string[] = [
    'bg-blue-500', 
    'bg-emerald-500', 
    'bg-orange-400', 
    'bg-purple-500', 
    'bg-rose-400',
    'bg-cyan-500'
  ];

  private baseUrl = 'http://51.178.87.234 :8021/api/documents';

  constructor(private http: HttpClient, private cdr: ChangeDetectorRef) {}

  ngOnInit() { this.chargerDocuments(); }

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

  chargerDocuments() {
    // 🟢 زدنا residence_id فـ Payload والـ Headers
    this.http.post(`${this.baseUrl}/principal`, { residence_id: this.getActivePropId() }, {
      headers: this.getHeaders(),
      withCredentials: true
    }).subscribe({
      next: (res: any) => {
        if (res.success) {
          this.residenceInfo = res.residence;
          this.totalSize = res.data.total_size;
          this.totalSizeBytes = Number(res.data.total_size_bytes) || 0;
          this.recentFiles = res.data.recent_files;
          this.folderContent = res.data.content;
          this.currentPath = '';
          this.pathHistory = [];
          
          const rawPercentage = (this.totalSizeBytes / (15 * 1024 * 1024 * 1024)) * 100;
          
          if (this.totalSizeBytes > 0 && rawPercentage < 0.1) {
              this.storagePercentage = 0.1;
          } else {
              this.storagePercentage = Number(Math.min(rawPercentage, 100).toFixed(1));
          }

          this.statistics = Object.keys(res.data.statistics).map(key => ({
            name: key, ...res.data.statistics[key],
            width: res.data.total_size_bytes > 0 ? (res.data.statistics[key].size / res.data.total_size_bytes) * 100 : 0
          }));
        }
        this.isLoading = false;
        this.cdr.detectChanges();
      }
    });
  }

  ouvrirDossier(item: any) {
    if (item.type !== 'folder') return;
    this.isLoading = true;

    // 🟢 زدنا residence_id
    this.http.post(`${this.baseUrl}/sous-dossier`, { 
        path: item.path,
        residence_id: this.getActivePropId()
    }, {
        headers: this.getHeaders(),
        withCredentials: true
    }).subscribe({
      next: (res: any) => {
        if (res.success) {
          this.pathHistory.push(this.currentPath);
          this.currentPath = item.path;
          this.folderContent = res.data;
        }
        this.isLoading = false;
        this.cdr.detectChanges();
      }
    });
  }

  retour() {
    const prev = this.pathHistory.pop();
    if (prev === undefined) return;
    if (prev === '') { this.chargerDocuments(); return; }
    this.ouvrirDossier({ type: 'folder', path: prev });
    this.pathHistory.pop(); 
  }

  getBreadcrumbs() {
    if (!this.currentPath) return ['Documents'];
    const parts = this.currentPath.split('/').slice(2); 
    return ['Documents', ...parts];
  }

  telecharger(path: string, type: string) {
    console.log(`📥 Lancement du téléchargement pour: ${path}`);
    
    this.cdr.detectChanges();

    // 🟢 زدنا residence_id
    const payload = {
      path: path,
      residence_id: this.getActivePropId()
    };

    // 🟢 زدنا الـ Headers
    this.http.post('http://51.178.87.234 :8021/api/documents/telecharger', payload, { 
        headers: this.getHeaders(),
        withCredentials: true,
        responseType: 'blob' 
    }).subscribe({
      next: (res: Blob) => {
        console.log('✅ Fichier reçu avec succès !');
        
        const mimeType = type === 'folder' ? 'application/zip' : (res.type || 'application/pdf');
        const blob = new Blob([res], { type: mimeType });
        
        const url = window.URL.createObjectURL(blob);
        const a = document.createElement('a');
        
        a.style.display = 'none';
        a.href = url;
        
        let fileName = path.split('/').pop() || 'document';
        if (type === 'folder' && !fileName.endsWith('.zip')) {
            fileName += '.zip';
        }
        
        a.download = fileName;
        document.body.appendChild(a);
        
        a.click(); 
        
        setTimeout(() => {
            window.URL.revokeObjectURL(url);
            a.remove();
            this.isLoading = false; 
            this.showToast("Téléchargement lancé avec succès.", "success");
            this.cdr.detectChanges();
        }, 1000);
      },
      error: (err) => {
        console.error("❌ Erreur de téléchargement:", err);
        
        if (err.error instanceof Blob) {
          const reader = new FileReader();
          reader.onload = (e: any) => {
            try {
              const laravelError = JSON.parse(e.target.result);
              this.showToast(laravelError.message || "Erreur Serveur", "error");
            } catch (e) {
              this.showToast("Impossible de télécharger ce fichier.", "error");
            }
          };
          reader.readAsText(err.error);
        } else {
          this.showToast("Erreur de connexion avec le serveur.", "error");
        }
        
        this.isLoading = false;
        this.cdr.detectChanges();
      }
    });
  }
}