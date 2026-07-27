import React, { useMemo } from 'react';
import styles from './atmosphere.module.css';

/**
 * Full-viewport cinematic atmosphere: vignette, candle glow pools,
 * drifting fog and floating golden dust motes. Purely decorative.
 */
export function Atmosphere({ moteCount = 34 }: { moteCount?: number }) {
  const motes = useMemo(
    () =>
      Array.from({ length: moteCount }).map((_, i) => {
        const size = 1 + Math.random() * 3.5;
        return {
          id: i,
          left: `${Math.random() * 100}%`,
          bottom: `${-5 - Math.random() * 20}%`,
          size,
          duration: `${16 + Math.random() * 26}s`,
          delay: `${Math.random() * 20}s`,
          opacity: 0.25 + Math.random() * 0.6,
        };
      }),
    [moteCount]
  );

  return (
    <div className={styles.atmosphere} aria-hidden="true">
      <div className={styles.fog} />
      <div className={`${styles.glow} ${styles.glowTop}`} />
      <div className={`${styles.glow} ${styles.glowLeft}`} />
      <div className={styles.dust}>
        {motes.map((m) => (
          <span
            key={m.id}
            className={styles.mote}
            style={{
              left: m.left,
              bottom: m.bottom,
              width: `${m.size}px`,
              height: `${m.size}px`,
              animationDuration: m.duration,
              animationDelay: m.delay,
              // @ts-expect-error custom prop
              '--o': m.opacity,
            }}
          />
        ))}
      </div>
      <div className={styles.vignette} />
    </div>
  );
}
