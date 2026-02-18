import React from 'react';
import './FeaturesSection.css';

const FeaturesSection = () => {
  const features = [
    {
      icon: '🔒',
      title: 'Safe & Secure',
      description: 'Bank-grade security with end-to-end encryption. Your transactions are protected with the highest security standards.',
      color: '#10b981'
    },
    {
      icon: '⚡',
      title: 'Lightning-Fast',
      description: 'Process payments in seconds. Real-time transaction processing with instant notifications and settlements.',
      color: '#3b82f6'
    },
    {
      icon: '✨',
      title: 'Simplicity',
      description: 'Easy integration with clean APIs. Get started in minutes with our developer-friendly documentation.',
      color: '#8b5cf6'
    }
  ];

  return (
    <section className="features-section">
      <div className="container">
        <div className="section-header">
          <h2 className="section-title">Why Choose PointWave?</h2>
          <p className="section-subtitle">
            Everything you need to accept payments and grow your business
          </p>
        </div>
        
        <div className="features-grid">
          {features.map((feature, index) => (
            <div 
              key={index} 
              className="feature-card"
              style={{ animationDelay: `${index * 0.15}s` }}
            >
              <div 
                className="feature-icon" 
                style={{ background: `${feature.color}15` }}
              >
                <span style={{ filter: `drop-shadow(0 0 8px ${feature.color}40)` }}>
                  {feature.icon}
                </span>
              </div>
              <h3 className="feature-title">{feature.title}</h3>
              <p className="feature-description">{feature.description}</p>
            </div>
          ))}
        </div>
      </div>
    </section>
  );
};

export default FeaturesSection;
