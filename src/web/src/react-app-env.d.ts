/// <reference types="react-scripts" />

// CSS Modules
declare module '*.module.css' {
  const classes: { [key: string]: string };
  export default classes;
}

declare module '*.module.scss' {
  const classes: { [key: string]: string };
  export default classes;
}

// Image files
declare module '*.png' {
  const src: string;
  export default src;
}

declare module '*.jpg' {
  const src: string;
  export default src;
}

declare module '*.jpeg' {
  const src: string;
  export default src;
}

declare module '*.gif' {
  const src: string;
  export default src;
}

// SVG files
declare module '*.svg' {
  import * as React from 'react';
  export const ReactComponent: React.FunctionComponent<React.SVGProps<SVGSVGElement>>;
  const src: string;
  export default src;
}

// PDF files
declare module '*.pdf' {
  const src: string;
  export default src;
}

// Font files
declare module '*.woff' {
  const src: string;
  export default src;
}

declare module '*.woff2' {
  const src: string;
  export default src;
}

declare module '*.ttf' {
  const src: string;
  export default src;
}

declare module '*.eot' {
  const src: string;
  export default src;
}

// Extend NodeJS namespace for environment variables
declare namespace NodeJS {
  interface ProcessEnv {
    NODE_ENV: 'development' | 'production' | 'test';
    REACT_APP_API_URL: string;
    REACT_APP_ADOBE_CLIENT_ID: string;
    REACT_APP_API_VERSION: string;
    REACT_APP_AUTH_TOKEN_KEY: string;
  }
}

// Adobe Acrobat PDF viewer SDK types
declare namespace AdobeDC {
  class View {
    constructor(config: { clientId: string; divId?: string });
    
    previewFile(config: {
      content: { location: { url: string } };
      metaData: { fileName: string; id?: string };
    }, viewerConfig?: {
      embedMode?: 'FULL_WINDOW' | 'SIZED_CONTAINER' | 'IN_LINE';
      defaultViewMode?: 'FIT_PAGE' | 'FIT_WIDTH' | 'FIT_HEIGHT';
      showAnnotationTools?: boolean;
      showDownloadPDF?: boolean;
      showPrintPDF?: boolean;
      showLeftHandPanel?: boolean;
    }): void;
    
    registerCallback(
      type: string,
      callback: (event: any) => void
    ): void;
    
    static CALLBACK_TYPES: {
      DOCUMENT_LOAD_SUCCESS: string;
      DOCUMENT_LOAD_FAILURE: string;
      PAGE_VIEW_CHANGED: string;
      VIEWER_EVENTS: string;
    };
  }
}

declare global {
  interface Window {
    AdobeDC?: {
      View: typeof AdobeDC.View;
    };
  }
}