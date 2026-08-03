/**
 * Shared Alpine state for one operational admin surface.
 *
 * This module owns presentation state only. Requests, authorization, validation,
 * redirects and business decisions remain with the server and the existing
 * form/modal contracts.
 *
 * State: pending, dialogOpen, feedback, lastRequestId and focus.
 * Requirements: 7.4, 7.5, 7.10, 8.9, 8.10
 */
export default function adminState() {
    let timeoutHandle = null;

    const clearRequestTimeout = () => {
        if (timeoutHandle !== null) {
            clearTimeout(timeoutHandle);
            timeoutHandle = null;
        }
    };

    const feedbackFor = (type, feedback) => {
        if (feedback !== null && typeof feedback === 'object') {
            return { ...feedback, type };
        }

        return { type, message: feedback ?? null };
    };

    return {
        pending: false,
        dialogOpen: false,
        feedback: null,
        lastRequestId: 0,
        focus: null,

        /**
         * Start one request and return its monotonically increasing version.
         * A pending request cannot be started twice.
         *
         * @param {{ timeoutMs?: number, timeoutFeedback?: object|string }} options
         * @returns {number|null}
         */
        beginRequest({ timeoutMs, timeoutFeedback } = {}) {
            if (this.pending) {
                return null;
            }

            clearRequestTimeout();
            const requestId = ++this.lastRequestId;
            this.pending = true;
            this.feedback = null;

            if (Number.isFinite(timeoutMs) && timeoutMs > 0) {
                timeoutHandle = setTimeout(() => {
                    this.failRequest(requestId, timeoutFeedback ?? { reason: 'timeout' });
                }, timeoutMs);
            }

            return requestId;
        },

        /**
         * Accept the current form submit without intercepting normal browser
         * navigation. A second submit is prevented while Loading_State is on.
         */
        onSubmit(event) {
            const requestId = this.beginRequest();

            if (requestId === null) {
                event?.preventDefault?.();

                return false;
            }

            return true;
        },

        /** @returns {boolean} whether the response belongs to the newest request */
        isCurrentRequest(requestId) {
            return requestId === this.lastRequestId;
        },

        /**
         * Replace Loading_State with exactly one success feedback when current.
         * Older responses are ignored and cannot overwrite newer state.
         */
        completeRequest(requestId, feedback = null) {
            return this.finishRequest(requestId, 'success', feedback);
        },

        /**
         * Replace Loading_State with exactly one Error_State when current.
         * Older responses are ignored and cannot overwrite newer state.
         */
        failRequest(requestId, feedback = null) {
            return this.finishRequest(requestId, 'error', feedback);
        },

        finishRequest(requestId, type, feedback) {
            if (!this.pending || !this.isCurrentRequest(requestId)) {
                return false;
            }

            clearRequestTimeout();
            this.pending = false;
            this.feedback = feedbackFor(type, feedback);

            return true;
        },

        /**
         * Backward-compatible release for native form navigation or callers
         * that do not have an asynchronous feedback payload.
         */
        settle(requestId = this.lastRequestId) {
            if (!this.isCurrentRequest(requestId)) {
                return false;
            }

            clearRequestTimeout();
            this.pending = false;

            return true;
        },

        openDialog(trigger = null) {
            this.focus = trigger ?? globalThis.document?.activeElement ?? null;
            this.dialogOpen = true;
        },

        closeDialog() {
            const trigger = this.focus;
            this.dialogOpen = false;
            this.focus = null;

            if (trigger?.focus) {
                const restore = () => trigger.focus();
                if (typeof this.$nextTick === 'function') {
                    this.$nextTick(restore);
                } else {
                    restore();
                }
            }
        },
    };
}
