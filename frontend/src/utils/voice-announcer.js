const DEFAULT_SPEECH_OPTIONS = {
  rate: 1,
  pitch: 1.1,
  volume: 1,
}

const CUSTOM_INTRO_AUDIO = {
  normal: '/audio/sunod-dayun-number.mp3',
  recall: '/audio/pagdali-palihog.mp3',
}

let activeAudio = null
let activeAudioResolve = null
let announcementVersion = 0

function getPreferredVoice() {
  if (typeof window === 'undefined' || !('speechSynthesis' in window)) {
    return null
  }

  const voices = window.speechSynthesis.getVoices()

  return (
    voices.find((voice) => voice.lang?.toLowerCase().startsWith('ceb')) ||
    voices.find((voice) => voice.lang?.toLowerCase().startsWith('fil')) ||
    voices.find((voice) => voice.lang?.toLowerCase().startsWith('en-ph')) ||
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

function stopActiveAudio() {
  if (!activeAudio) {
    return
  }

  activeAudio.pause()
  activeAudio.currentTime = 0
  activeAudio.onended = null
  activeAudio.onerror = null

  if (activeAudioResolve) {
    activeAudioResolve(false)
    activeAudioResolve = null
  }

  activeAudio = null
}

function createUtterance(message) {
  const utterance = new SpeechSynthesisUtterance(message)
  utterance.rate = DEFAULT_SPEECH_OPTIONS.rate
  utterance.pitch = DEFAULT_SPEECH_OPTIONS.pitch
  utterance.volume = DEFAULT_SPEECH_OPTIONS.volume
  utterance.lang = 'fil-PH'

  const voice = getPreferredVoice()
  if (voice) {
    utterance.voice = voice
    utterance.lang = voice.lang
  }

  return utterance
}

function speakMessage(message) {
  const utterance = createUtterance(message)
  window.speechSynthesis.speak(utterance)
}

function playIntroAudio(source, version) {
  if (!source) {
    return Promise.resolve(false)
  }

  return new Promise((resolve) => {
    const audio = new Audio(source)
    activeAudio = audio
    activeAudioResolve = resolve
    audio.preload = 'auto'

    const cleanup = () => {
      if (activeAudio === audio) {
        activeAudio = null
      }
      if (activeAudioResolve === resolve) {
        activeAudioResolve = null
      }
      audio.onended = null
      audio.onerror = null
    }

    audio.onended = () => {
      cleanup()
      resolve(announcementVersion === version)
    }

    audio.onerror = () => {
      cleanup()
      resolve(false)
    }

    audio
      .play()
      .then(() => {
        if (announcementVersion !== version) {
          cleanup()
          resolve(false)
        }
      })
      .catch(() => {
        cleanup()
        resolve(false)
      })
  })
}

export function announceQueueNumber(queueNumber, options = {}) {
  if (typeof window === 'undefined' || !('speechSynthesis' in window) || !('SpeechSynthesisUtterance' in window)) {
    return
  }

  const spokenQueueNumber = formatQueueNumberForSpeech(queueNumber)
  const isRecall = Boolean(options.isRecall)

  if (!spokenQueueNumber) {
    return
  }

  // Interrupt any queued speech so the latest callout is spoken immediately.
  announcementVersion += 1
  const currentVersion = announcementVersion
  stopActiveAudio()
  window.speechSynthesis.cancel()

  const fallbackMessage = isRecall
    ? `Pagdali palihog ${spokenQueueNumber}.`
    : `Sunod dayun number ${spokenQueueNumber}.`

  const introSource = isRecall ? CUSTOM_INTRO_AUDIO.recall : CUSTOM_INTRO_AUDIO.normal

  void playIntroAudio(introSource, currentVersion).then((playedIntro) => {
    if (announcementVersion !== currentVersion) {
      return
    }

    if (playedIntro) {
      speakMessage(spokenQueueNumber)
      return
    }

    speakMessage(fallbackMessage)
  })
}

export function stopAnnouncements() {
  if (typeof window !== 'undefined' && 'speechSynthesis' in window) {
    announcementVersion += 1
    stopActiveAudio()
    window.speechSynthesis.cancel()
  }
}
