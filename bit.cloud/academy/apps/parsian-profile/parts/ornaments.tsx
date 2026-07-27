import React from 'react';

/**
 * Ornamental SVG flourishes used across the page to create a
 * handcrafted, museum-quality antique-gold aesthetic.
 */

/** A horizontal divider with a central diamond and tapering filigree wings. */
export function GoldDivider({ width = 260, className }: { width?: number; className?: string }) {
  return (
    <svg
      className={className}
      width={width}
      height="26"
      viewBox="0 0 260 26"
      fill="none"
      aria-hidden="true"
    >
      <defs>
        <linearGradient id="gd-line" x1="0" y1="0" x2="260" y2="0" gradientUnits="userSpaceOnUse">
          <stop stopColor="#7c5f28" stopOpacity="0" />
          <stop offset="0.5" stopColor="#e4c877" />
          <stop offset="1" stopColor="#7c5f28" stopOpacity="0" />
        </linearGradient>
      </defs>
      <path d="M8 13 H108" stroke="url(#gd-line)" strokeWidth="1.4" />
      <path d="M152 13 H252" stroke="url(#gd-line)" strokeWidth="1.4" />
      <path d="M118 13 C124 6 136 6 142 13 C136 20 124 20 118 13 Z" stroke="#e4c877" strokeWidth="1.2" fill="none" />
      <path d="M130 3 L134 13 L130 23 L126 13 Z" fill="#e4c877" />
      <circle cx="130" cy="13" r="2" fill="#0a0806" />
      <circle cx="110" cy="13" r="1.6" fill="#c9a24b" />
      <circle cx="150" cy="13" r="1.6" fill="#c9a24b" />
    </svg>
  );
}

/** Corner filigree — placed in card corners. Mirror with CSS transforms. */
export function CornerFlourish({ size = 62, className }: { size?: number; className?: string }) {
  return (
    <svg
      className={className}
      width={size}
      height={size}
      viewBox="0 0 62 62"
      fill="none"
      aria-hidden="true"
    >
      <path
        d="M4 4 L30 4 M4 4 L4 30 M4 4 C22 6 30 14 32 32 M12 4 C12 12 8 16 4 16"
        stroke="#c9a24b"
        strokeWidth="1.3"
        fill="none"
        strokeLinecap="round"
      />
      <path d="M4 4 C16 4 24 12 24 24" stroke="#e4c877" strokeWidth="0.8" fill="none" opacity="0.7" />
      <circle cx="4" cy="4" r="2.4" fill="#e4c877" />
      <circle cx="33" cy="33" r="1.8" fill="#c9a24b" />
    </svg>
  );
}

/** A small heraldic music emblem — used as a seal / crest. */
export function Crest({ size = 54, className }: { size?: number; className?: string }) {
  return (
    <svg className={className} width={size} height={size} viewBox="0 0 54 54" fill="none" aria-hidden="true">
      <defs>
        <linearGradient id="crest-g" x1="0" y1="0" x2="0" y2="54" gradientUnits="userSpaceOnUse">
          <stop stopColor="#f7ecc9" />
          <stop offset="1" stopColor="#a8823a" />
        </linearGradient>
      </defs>
      <path d="M27 2 L50 10 V27 C50 41 40 50 27 53 C14 50 4 41 4 27 V10 Z" stroke="url(#crest-g)" strokeWidth="1.4" fill="rgba(20,16,9,0.6)" />
      <path d="M27 8 L44 14 V27 C44 37 37 44 27 47 C17 44 10 37 10 27 V14 Z" stroke="#7c5f28" strokeWidth="0.8" fill="none" />
      <path d="M22 16 V34 M22 34 C22 37 19 38 17 37 C15 36 16 33 19 33 C20 33 22 33 22 34 Z" stroke="url(#crest-g)" strokeWidth="1.5" fill="none" strokeLinecap="round" />
      <path d="M32 14 V32 M32 32 C32 35 29 36 27 35 C25 34 26 31 29 31 C30 31 32 31 32 32 Z" stroke="url(#crest-g)" strokeWidth="1.5" fill="none" strokeLinecap="round" />
      <path d="M22 16 L32 14" stroke="url(#crest-g)" strokeWidth="1.5" strokeLinecap="round" />
    </svg>
  );
}

/** Decorative section eyebrow: gilt flourish + label + flourish. */
export function SectionMark({ children }: { children: React.ReactNode }) {
  return (
    <div style={{ display: 'flex', alignItems: 'center', justifyContent: 'center', gap: '1.1rem' }}>
      <GoldDivider width={110} />
      <span
        style={{
          fontFamily: "'Cinzel', serif",
          letterSpacing: '0.42em',
          fontSize: '0.72rem',
          color: '#c9a24b',
          textTransform: 'uppercase',
          whiteSpace: 'nowrap',
        }}
      >
        {children}
      </span>
      <GoldDivider width={110} />
    </div>
  );
}
