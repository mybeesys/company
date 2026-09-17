import React, { Component } from 'react';

export default class WidgetErrorBoundary extends Component {
    constructor(props) {
        super(props);
        this.state = { hasError: false };
    }

    static getDerivedStateFromError() {
        return { hasError: true };
    }

    componentDidCatch() {
        /* isolate widget crash from the rest of the dashboard */
    }

    render() {
        if (this.state.hasError) {
            return (
                <section className="ed-card">
                    <div className="ed-card-head">
                        <h2>{this.props.title || ''}</h2>
                    </div>
                    <div className="ed-widget-state" role="alert">
                        {this.props.fallback || 'تعذر عرض هذا القسم'}
                    </div>
                </section>
            );
        }
        return this.props.children;
    }
}
