import React from 'react';
import { Routes, Route } from 'react-router-dom';
import theme from './styles/theme.module.css';
import styles from './parsian-profile.module.css';
import { Atmosphere } from './parts/atmosphere.js';
import { Navbar } from './parts/navbar.js';
import { Hero } from './sections/hero.js';
import { Features } from './sections/features.js';
import { Dossier } from './sections/dossier.js';
import { Schedule } from './sections/schedule.js';
import { QuoteCard } from './sections/quote.js';
import { Footer } from './sections/footer.js';

/** The full teacher profile experience for Parsian Music Academy. */
export function TeacherProfile() {
  return (
    <div className={`${theme.theme} ${styles.page}`} dir="rtl">
      <Atmosphere />
      <Navbar />
      <div className={styles.content}>
        <Hero />
        <Features />
        <Dossier />
        <Schedule />
        <QuoteCard />
        <Footer />
      </div>
    </div>
  );
}

export function ParsianProfile() {
  return (
    <Routes>
      <Route path="/" element={<TeacherProfile />} />
    </Routes>
  );
}
