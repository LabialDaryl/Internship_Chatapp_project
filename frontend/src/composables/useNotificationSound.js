export function useNotificationSound() {
  const playChime = () => {
    try {
      const AudioContext = window.AudioContext || window.webkitAudioContext
      if (!AudioContext) return

      const ctx = new AudioContext()
      
      // Frequency 1 (880 Hz - A5)
      const osc1 = ctx.createOscillator()
      const gain1 = ctx.createGain()
      osc1.type = 'sine'
      osc1.frequency.setValueAtTime(880, ctx.currentTime)
      gain1.gain.setValueAtTime(0.08, ctx.currentTime)
      gain1.gain.exponentialRampToValueAtTime(0.001, ctx.currentTime + 0.25)
      
      osc1.connect(gain1)
      gain1.connect(ctx.destination)
      osc1.start(ctx.currentTime)
      osc1.stop(ctx.currentTime + 0.25)

      // Frequency 2 (1318.5 Hz - E6)
      const osc2 = ctx.createOscillator()
      const gain2 = ctx.createGain()
      osc2.type = 'sine'
      osc2.frequency.setValueAtTime(1318.5, ctx.currentTime + 0.1)
      gain2.gain.setValueAtTime(0.08, ctx.currentTime + 0.1)
      gain2.gain.exponentialRampToValueAtTime(0.001, ctx.currentTime + 0.35)

      osc2.connect(gain2)
      gain2.connect(ctx.destination)
      osc2.start(ctx.currentTime + 0.1)
      osc2.stop(ctx.currentTime + 0.35)
    } catch (e) {
      // AudioContext autoplay restriction safeguard
    }
  }

  let ringInterval = null

  const playRingtone = () => {
    stopRingtone()
    playChime()
    ringInterval = setInterval(() => {
      playChime()
    }, 1500)
  }

  const stopRingtone = () => {
    if (ringInterval) {
      clearInterval(ringInterval)
      ringInterval = null
    }
  }

  return { playChime, playRingtone, stopRingtone }
}
