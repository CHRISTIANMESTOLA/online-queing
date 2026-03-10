const DEFAULT_SPEECH_OPTIONS = {
  rate: 0.9,
  pitch: 1.1,
  volume: 1,
}

function getPreferredVoice() {
  if (typeof window === 'undefined' || !('speechSynthesis' in window)) {
    return null
  }

  const voices = window.speechSynthesis.getVoices()

  return (
    voices.find((voice) => voice.lang?.toLowerCase().startsWith('en-us')) ||
    voices.find((voice) => voice.lang?.toLowerCase().startsWith('en')) ||
    null
  )
}

function formatQueueNumberForSpeech(queueNumber) {
  return String(queueNumber || '')
    .toUpperCase()
    .replace(/[^A-Z0-9]/g, ' ')
    .split('')
    .map((character) => (/[A-Z0-9]/.test(character) ? character : ' '))
    .join(' ')
    .replace(/\s+/g, ' ')
    .trim()
}

export function announceQueueNumber(queueNumber, officeName) {
  if (typeof window === 'undefined' || !('speechSynthesis' in window) || !('SpeechSynthesisUtterance' in window)) {
    return
  }

  const spokenQueueNumber = formatQueueNumberForSpeech(queueNumber)

  if (!spokenQueueNumber) {
    return
  }

  const message = officeName
    ? `Now serving ${spokenQueueNumber} at ${officeName}.`
    : `Now serving ${spokenQueueNumber}.`

  const utterance = new SpeechSynthesisUtterance(message)
  utterance.rate = DEFAULT_SPEECH_OPTIONS.rate
  utterance.pitch = DEFAULT_SPEECH_OPTIONS.pitch
  utterance.volume = DEFAULT_SPEECH_OPTIONS.volume

  const voice = getPreferredVoice()
  if (voice) {
    utterance.voice = voice
  }

  window.speechSynthesis.speak(utterance)
}

export function stopAnnouncements() {
  if (typeof window !== 'undefined' && 'speechSynthesis' in window) {
    window.speechSynthesis.cancel()
  }
}
