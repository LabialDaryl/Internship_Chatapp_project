class WebRTCManager {
  constructor() {
    this.peerConnection = null
    this.localStream = null
    this.remoteStream = null
    this.screenTrack = null
    this.onRemoteStream = null
    this.onIceCandidate = null

    this.rtcConfig = {
      iceServers: [
        { urls: 'stun:stun.l.google.com:19020' },
        { urls: 'stun:stun1.l.google.com:19020' },
        { urls: 'stun:stun2.l.google.com:19020' }
      ]
    }
  }

  async initLocalStream(video = true, audio = true) {
    this.localStream = await navigator.mediaDevices.getUserMedia({ video, audio })
    return this.localStream
  }

  initPeerConnection() {
    if (this.peerConnection) return this.peerConnection

    this.peerConnection = new RTCPeerConnection(this.rtcConfig)

    this.remoteStream = new MediaStream()

    if (this.localStream) {
      this.localStream.getTracks().forEach(track => {
        this.peerConnection.addTrack(track, this.localStream)
      })
    }

    this.peerConnection.ontrack = (event) => {
      event.streams[0].getTracks().forEach(track => {
        if (!this.remoteStream.getTracks().includes(track)) {
          this.remoteStream.addTrack(track)
        }
      })
      if (this.onRemoteStream) {
        this.onRemoteStream(this.remoteStream)
      }
    }

    this.peerConnection.onicecandidate = (event) => {
      if (event.candidate && this.onIceCandidate) {
        this.onIceCandidate(event.candidate)
      }
    }

    return this.peerConnection
  }

  async createOffer() {
    this.initPeerConnection()
    const offer = await this.peerConnection.createOffer()
    await this.peerConnection.setLocalDescription(offer)
    return offer
  }

  async handleOffer(offer) {
    this.initPeerConnection()
    await this.peerConnection.setRemoteDescription(new RTCSessionDescription(offer))
    const answer = await this.peerConnection.createAnswer()
    await this.peerConnection.setLocalDescription(answer)
    return answer
  }

  async handleAnswer(answer) {
    if (this.peerConnection) {
      await this.peerConnection.setRemoteDescription(new RTCSessionDescription(answer))
    }
  }

  async addIceCandidate(candidate) {
    if (this.peerConnection) {
      await this.peerConnection.addIceCandidate(new RTCIceCandidate(candidate))
    }
  }

  toggleAudio(enabled) {
    if (this.localStream) {
      this.localStream.getAudioTracks().forEach(track => {
        track.enabled = enabled
      })
    }
  }

  toggleVideo(enabled) {
    if (this.localStream) {
      this.localStream.getVideoTracks().forEach(track => {
        track.enabled = enabled
      })
    }
  }

  async startScreenShare() {
    const displayStream = await navigator.mediaDevices.getDisplayMedia({ video: true })
    this.screenTrack = displayStream.getVideoTracks()[0]
    
    const videoSender = this.peerConnection?.getSenders().find(s => s.track?.kind === 'video')
    if (videoSender) {
      videoSender.replaceTrack(this.screenTrack)
    }

    this.screenTrack.onended = () => {
      this.stopScreenShare()
    }
  }

  stopScreenShare() {
    if (this.screenTrack && this.localStream) {
      const originalVideoTrack = this.localStream.getVideoTracks()[0]
      const videoSender = this.peerConnection?.getSenders().find(s => s.track?.kind === 'video')
      if (videoSender && originalVideoTrack) {
        videoSender.replaceTrack(originalVideoTrack)
      }
      this.screenTrack.stop()
      this.screenTrack = null
    }
  }

  close() {
    if (this.screenTrack) this.screenTrack.stop()
    if (this.localStream) {
      this.localStream.getTracks().forEach(track => track.stop())
    }
    if (this.peerConnection) {
      this.peerConnection.close()
    }
    this.peerConnection = null
    this.localStream = null
    this.remoteStream = null
  }
}

export default new WebRTCManager()
