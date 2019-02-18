import Quill, { DeltaStatic, RangeStatic } from 'quill';

export abstract class Segmenter {
  protected readonly _segments: Map<string, RangeStatic> = new Map<string, RangeStatic>();
  protected _lastSegmentRef: string = '';

  constructor(protected readonly editor: Quill) {}

  get lastSegmentRef(): string {
    return this._lastSegmentRef;
  }

  get segments(): IterableIterator<[string, RangeStatic]> {
    return this._segments.entries();
  }

  get text(): string {
    const text = this.editor.getText();
    return text.endsWith('\n') ? text.substr(0, text.length - 1) : text;
  }

  update(textChange: boolean): void {
    if (this._lastSegmentRef === '' || textChange) {
      this.updateSegments();
    }
  }

  reset(): void {
    this._segments.clear();
    this._lastSegmentRef = '';
  }

  getSegmentRange(ref: string): RangeStatic {
    if (this.text === '') {
      return { index: 0, length: 0 };
    }

    return this._segments.has(ref) ? this._segments.get(ref) : { index: this.text.length, length: 0 };
  }

  getSegmentRef(range: RangeStatic): string {
    let segmentRef: string;
    let maxOverlap = -1;
    if (range != null) {
      for (const [ref, segmentRange] of this.segments) {
        const segEnd = segmentRange.index + segmentRange.length;
        if (range.index <= segEnd) {
          const rangeEnd = range.index + range.length;
          const overlap = Math.min(rangeEnd, segEnd) - Math.max(range.index, segmentRange.index);
          if (overlap > maxOverlap) {
            segmentRef = ref;
            maxOverlap = overlap;
          }
          if (rangeEnd <= segEnd) {
            break;
          }
        }
      }
    }
    return segmentRef;
  }

  protected abstract updateSegments(): void;
}
