<template>
  <div class="relative min-h-screen w-full overflow-hidden bg-[#0a0216] flex items-center justify-center p-4 selection:bg-purple-500 selection:text-white">
    <!-- Ambient Background Color Orbs -->
    <div class="absolute top-[-10%] left-[-5%] w-[550px] h-[550px] rounded-full bg-gradient-to-br from-purple-700/40 to-fuchsia-800/20 blur-[130px] pointer-events-none"></div>
    <div class="absolute bottom-[-15%] right-[-5%] w-[600px] h-[600px] rounded-full bg-gradient-to-tr from-indigo-900/50 via-purple-800/30 to-fuchsia-600/20 blur-[140px] pointer-events-none"></div>
    <div class="absolute top-[30%] right-[10%] w-[350px] h-[350px] rounded-full bg-purple-500/20 blur-[100px] pointer-events-none"></div>

    <!-- Soft Bokeh Circles / Light Bubbles -->
    <div class="absolute top-[18%] left-[8%] w-8 h-8 rounded-full bg-purple-300/20 blur-[2px] pointer-events-none"></div>
    <div class="absolute top-[28%] left-[12%] w-14 h-14 rounded-full bg-purple-400/25 blur-[3px] pointer-events-none"></div>
    <div class="absolute top-[65%] left-[6%] w-20 h-20 rounded-full bg-fuchsia-400/15 blur-[4px] pointer-events-none"></div>
    <div class="absolute top-[78%] left-[14%] w-10 h-10 rounded-full bg-purple-300/20 blur-[2px] pointer-events-none"></div>

    <div class="absolute top-[12%] right-[18%] w-12 h-12 rounded-full bg-purple-300/20 blur-[3px] pointer-events-none"></div>
    <div class="absolute top-[48%] right-[5%] w-8 h-8 rounded-full bg-white/40 blur-[1px] pointer-events-none"></div>
    <div class="absolute top-[72%] right-[12%] w-[70px] h-[70px] rounded-full bg-purple-400/20 blur-[4px] pointer-events-none"></div>

    <!-- Left Background Blurred Chat Window Mockup -->
    <div class="hidden lg:block absolute left-[4%] top-[18%] w-[380px] h-[480px] rounded-2xl border border-white/10 bg-white/[0.03] backdrop-blur-[6px] shadow-2xl p-4 overflow-hidden pointer-events-none opacity-40 transform -rotate-1">
      <!-- Window Controls -->
      <div class="flex items-center gap-1.5 mb-6 opacity-70">
        <span class="w-3 h-3 rounded-full bg-rose-500/80"></span>
        <span class="w-3 h-3 rounded-full bg-amber-500/80"></span>
        <span class="w-3 h-3 rounded-full bg-emerald-500/80"></span>
      </div>
      <!-- Mock Messages & Avatar -->
      <div class="space-y-4">
        <div class="flex items-start gap-3">
          <div class="w-8 h-8 rounded-full bg-purple-500/30"></div>
          <div class="w-48 h-12 rounded-2xl bg-white/10 backdrop-blur-md"></div>
        </div>
        <div class="flex items-start gap-3 justify-end pt-8">
          <div class="w-56 h-14 rounded-2xl bg-purple-600/30 backdrop-blur-md border border-purple-400/20"></div>
          <div class="w-8 h-8 rounded-full bg-fuchsia-500/40"></div>
        </div>
      </div>
      <!-- Node Connection Line SVG -->
      <svg class="absolute inset-0 w-full h-full text-purple-400/30 pointer-events-none" viewBox="0 0 380 480" fill="none">
        <path d="M 40 180 Q 120 280 180 220 T 320 340" stroke="currentColor" stroke-width="2" stroke-dasharray="4 4" fill="none"/>
        <circle cx="180" cy="220" r="5" fill="#c084fc" />
        <circle cx="40" cy="180" r="4" fill="#a855f7" />
      </svg>
    </div>

    <!-- Right Background Blurred Chat Window Mockup -->
    <div class="hidden lg:block absolute right-[4%] top-[15%] w-[420px] h-[520px] rounded-2xl border border-white/10 bg-white/[0.03] backdrop-blur-[6px] shadow-2xl p-5 overflow-hidden pointer-events-none opacity-40 transform rotate-1">
      <!-- Window Top Header -->
      <div class="flex items-center justify-between pb-4 border-b border-white/10 opacity-60">
        <div class="w-24 h-3 rounded-full bg-white/20"></div>
        <div class="w-4 h-4 rounded-full bg-white/20"></div>
      </div>
      <!-- Chat Bubbles -->
      <div class="mt-8 space-y-6">
        <div class="p-3 rounded-2xl bg-purple-500/20 border border-purple-400/20 max-w-[75%] space-y-1.5">
          <div class="w-32 h-2.5 rounded bg-white/30"></div>
          <div class="w-44 h-2 rounded bg-white/20"></div>
        </div>
        <div class="ml-auto p-3 rounded-2xl bg-fuchsia-600/30 border border-fuchsia-400/20 max-w-[70%] space-y-1.5">
          <div class="w-28 h-2.5 rounded bg-white/40"></div>
          <div class="w-36 h-2 rounded bg-white/20"></div>
        </div>
      </div>
      <!-- Bottom Input Mockup -->
      <div class="absolute bottom-5 left-5 right-5 h-10 rounded-full border border-white/10 bg-white/5 flex items-center px-4 justify-between">
        <div class="w-16 h-2 rounded bg-white/20"></div>
        <div class="flex gap-2">
          <div class="w-3 h-3 rounded-full bg-purple-400/50"></div>
          <div class="w-3 h-3 rounded-full bg-fuchsia-400/50"></div>
        </div>
      </div>
      <!-- Node Connection Line SVG -->
      <svg class="absolute inset-0 w-full h-full text-fuchsia-400/30 pointer-events-none" viewBox="0 0 420 520" fill="none">
        <path d="M 60 120 C 140 200, 220 150, 320 310" stroke="currentColor" stroke-width="2" fill="none"/>
        <circle cx="160" cy="180" r="5" fill="#e879f9" />
        <circle cx="320" cy="310" r="4" fill="#a855f7" />
      </svg>
    </div>

    <!-- Decorative 4-Point Sparkle Star Accent (Bottom Right) -->
    <div class="absolute bottom-10 right-14 text-purple-300/40 pointer-events-none animate-pulse">
      <svg class="w-8 h-8" viewBox="0 0 24 24" fill="currentColor">
        <path d="M12 0L14.59 9.41L24 12L14.59 14.59L12 24L9.41 14.59L0 12L9.41 9.41L12 0Z" />
      </svg>
    </div>

    <!-- Main Content Slot (Form Card) -->
    <div class="relative z-10 w-full flex justify-center">
      <slot></slot>
    </div>
  </div>
</template>

<script setup>
// Background Component for Auth pages
</script>
