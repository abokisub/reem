import React from 'react';
import './ComingFeaturesSection.css';

const ComingFeaturesSection = () => {
  const comingFeatures = [
    {
      icon: '🛍️',
      title: 'Storefront',
      description: 'Create your online store and start selling in minutes',
      status: 'Coming Soon'
    },
    {
      icon: '🔗',
      title: 'Payment Links',
      description: 'Generate payment links and share with customers',
      status: 'Coming Soon'
    },
    {
      icon: '📊',
      title: 'Advanced Analytics',
      description: 'Deep insights into your business performance',
      status: 'Coming Soon'
    },
    {
      icon: '🤝',
      title: 'Multi-Currency',
      description: 'Accept payments in multiple currencies',
      status: 'Coming Soon'
    }
  ];

  return (
    <section className="coming-features-section">
      <div className="container">
        <div className="section-header">
          <h2 className="section-title">What's Coming Next</h2>
          <p className="section-subtitle">
            We're constantly building new features to help you grow
          </p>
        </div>
        
        <div className="coming-features-grid">
          {comingFeatures.map((feature, index) => (
            <div 
              key={index} 
              className="coming-feature-card"
              style={{ animationDelay: `${index * 0.1}s` }}
            >
              <div className="coming-badge">{feature.status}</div>
              <div className="coming-icon">{feature.icon}</div>
              <h3 className="coming-title">{feature.title}</h3>
              <p className="coming-description">{feature.description}</p>
            </div>
          ))}
        </div>

        <div className="cta-box">
          <h3 className="cta-title">Ready to Get Started?</h3>
          <p className="cta-description">
            Join thousands of businesses already using PointWave
          </p>
          <div className="cta-buttons">
            <a 
              href="https://app.pointwave.ng/auth/register" 
              className="btn btn-primary btn-large"
            >
              Create Free Account
            </a>
            <a 
              href="https://app.pointwave.ng/documentation/home" 
              className="btn btn-outline btn-large"
            >
              Read Documentation
            </a>
          </div>
        </div>
      </div>
    </section>
  );
};

export default ComingFeaturesSection;
