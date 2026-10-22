import { Injectable } from '@angular/core';

@Injectable({
  providedIn: 'root'
})
export class StorageService {

  setItem(key: string, value: string): void {
    localStorage.setItem(key, value);
  }

  getItem(key: string, clear: boolean = false): string | null {
    const item = localStorage.getItem(key);
    if(clear) localStorage.removeItem(key);

    return item;
  }

  removeItem(key: string): void {
    localStorage.removeItem(key);
  }

  clear(): void {
    localStorage.clear();
  }
}