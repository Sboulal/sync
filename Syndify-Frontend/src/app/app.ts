import { Component, OnInit } from '@angular/core';
import { RouterOutlet } from '@angular/router';
import { PageTitleService } from './core/services/page-title';

@Component({
  selector: 'app-root',
  standalone: true,
  imports: [RouterOutlet],
  templateUrl: './app.html',
  styleUrl: './app.css'
})
export class App implements OnInit {
  constructor(private pageTitleService: PageTitleService) {}

  ngOnInit() {
    this.pageTitleService.init();
  }
}