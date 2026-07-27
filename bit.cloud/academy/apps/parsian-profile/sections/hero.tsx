import React from 'react';
import { PortraitFrame } from '../parts/portrait-frame.js';
import { teacher } from '../content.js';
import styles from './hero.module.css';
import backdrop from '../assets/hall-backdrop.png';

/**
 * Cinematic hero: gothic-hall backdrop, gilt display name, role,
 * ornate portrait frame and a floating experience seal.
 */
export function Hero() {
  return (
    <header className={styles.hero} id="hero">
      <div className={styles.backdrop}>
        <img className={styles.backdropImg} src={backdrop} alt="" aria-hidden="true" />
      </div>

      <div className={styles.text}>
        <div className={styles.eyebrow}>
          <span className={styles.eyebrowLine} />
          <span className={styles.eyebrowText}>Parsian Conservatoire · پروفایل استاد</span>
          <span className={styles.eyebrowLine} />
        </div>

        <h1 className={styles.name}>{teacher.name}</h1>
        <div className={styles.latinName}>{teacher.latinName}</div>
        <p className={styles.role}>{teacher.role}</p>
        <p className={styles.tagline}>{teacher.tagline}</p>

        <div className={styles.actions}>
          <button className={styles.primary} type="button">
            رزرو کلاس با استاد
          </button>
          <button className={styles.ghost} type="button">
            مشاهدهٔ اجراها
          </button>
        </div>
      </div>

      <div className={styles.portraitSide}>
        <PortraitFrame name={teacher.name} />
        <div className={styles.badge}>
          <div>
            <div className={styles.badgeNum}>{teacher.experienceYears}</div>
            <div className={styles.badgeLabel}>سال تدریس</div>
          </div>
        </div>
      </div>

      <div className={styles.scrollCue}>
        <span>Scroll</span>
        <div className={styles.scrollLine} />
      </div>
    </header>
  );
}
