import React from 'react';
import styles from './portrait-frame.module.css';
import portrait from '../assets/maestro-portrait.png';

/** Small gilt rosette used in the frame's lower corners. */
function Rosette() {
  return (
    <svg width="44" height="44" viewBox="0 0 44 44" fill="none" aria-hidden="true">
      <path
        d="M22 6 C26 14 30 18 38 22 C30 26 26 30 22 38 C18 30 14 26 6 22 C14 18 18 14 22 6 Z"
        fill="currentColor"
        opacity="0.9"
      />
      <circle cx="22" cy="22" r="4" fill="#120d09" />
      <circle cx="22" cy="22" r="2" fill="currentColor" />
    </svg>
  );
}

/** Ornate arched apex medallion. */
function ApexCrest() {
  return (
    <svg width="90" height="70" viewBox="0 0 90 70" fill="none" aria-hidden="true">
      <defs>
        <linearGradient id="apex-g" x1="45" y1="0" x2="45" y2="70" gradientUnits="userSpaceOnUse">
          <stop stopColor="#f7ecc9" />
          <stop offset="1" stopColor="#7c5f28" />
        </linearGradient>
      </defs>
      <path d="M45 4 C58 4 66 14 66 26 C66 40 55 50 45 66 C35 50 24 40 24 26 C24 14 32 4 45 4 Z" fill="url(#apex-g)" stroke="#3a2c15" strokeWidth="1" />
      <path d="M45 12 C53 12 58 19 58 27 C58 37 50 45 45 55 C40 45 32 37 32 27 C32 19 37 12 45 12 Z" fill="#160f08" />
      <path d="M41 22 V38 M41 38 C41 41 38 42 36 41 C34 40 35 37 38 37 C39 37 41 37 41 38 Z" stroke="url(#apex-g)" strokeWidth="1.4" fill="none" strokeLinecap="round" />
      <path d="M49 20 V36 M49 36 C49 39 46 40 44 39 C42 38 43 35 46 35 C47 35 49 35 49 36 Z" stroke="url(#apex-g)" strokeWidth="1.4" fill="none" strokeLinecap="round" />
      <path d="M41 22 L49 20" stroke="url(#apex-g)" strokeWidth="1.4" strokeLinecap="round" />
      <path d="M10 58 C24 54 34 58 45 60 C56 58 66 54 80 58" stroke="url(#apex-g)" strokeWidth="1.4" fill="none" strokeLinecap="round" />
    </svg>
  );
}

/** Engraved filigree that overlays the golden frame band. */
function FrameEngraving() {
  return (
    <svg className={styles.engrave} viewBox="0 0 400 520" preserveAspectRatio="none" fill="none">
      <path d="M200 8 C120 8 60 70 60 160 M200 8 C280 8 340 70 340 160" stroke="currentColor" strokeWidth="1.2" fill="none" />
      <path d="M30 200 C34 320 34 420 30 500 M370 200 C366 320 366 420 370 500" stroke="currentColor" strokeWidth="1.2" fill="none" />
      <path d="M60 250 C48 258 48 274 60 282 M340 250 C352 258 352 274 340 282" stroke="currentColor" strokeWidth="1" fill="none" />
      <path d="M60 360 C48 368 48 384 60 392 M340 360 C352 368 352 384 340 392" stroke="currentColor" strokeWidth="1" fill="none" />
    </svg>
  );
}

/**
 * Highly decorative antique portrait frame — layered carved-stone
 * band, engraved gold filigree, arched apex crest, corner rosettes
 * and a nameplate cartouche.
 */
export function PortraitFrame({ name }: { name: string }) {
  return (
    <div className={styles.wrap}>
      <div className={styles.halo} />
      <div className={styles.frame}>
        <FrameEngraving />
        <div className={styles.bevel}>
          <div className={styles.photoWrap}>
            <img className={styles.photo} src={portrait} alt={name} />
          </div>
        </div>
      </div>
      <div className={styles.apex}>
        <ApexCrest />
      </div>
      <div className={`${styles.rosette} ${styles.rosetteL}`}>
        <Rosette />
      </div>
      <div className={`${styles.rosette} ${styles.rosetteR}`}>
        <Rosette />
      </div>
      <div className={styles.plate}>
        <span className={styles.plateText}>Maestro · استاد برجسته</span>
      </div>
    </div>
  );
}
