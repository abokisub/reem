import React from 'react';
import './ForStartupsSection.css';

const ForStartupsSection = () => {
  const benefits = [
    {
      icon: '🚀',
      title: 'Quick Setup',
      description: 'Get started in minutes with our simple onboarding process'
    },
    {
      icon: '💰',
      title: 'Competitive Rates',
      description: 'Transparent pricing with no hidden fees'
    },
    {
      icon: '📊',
      title: 'Real-time Analytics',
      description: 'Track your business performance with detailed insights'
    },
    {
      icon: '🔧',
      title: 'Developer-Friendly',
      description: 'Clean APIs and comprehensive documentation'
    }
  ];

  return (
    <section className="for-startups-section">
      <div className="container">
        <div className="startups-content">
          <div className="startups-text">
            <h2 className="startups-title">
              Built for Startups & Business Owners
            </h2>
            <p className="startups-description">
              Whether you're a startup or an established business, PointWave provides 
              the tools you need to accept payments, manage transactions, and grow your revenue.
            </p>
            
            <div className="benefits-list">
              {benefits.map((benefit, index) => (
                <div 
                  key={index} 
                  className="benefit-item"
                  style={{ animationDelay: `${index * 0.1}s` }}
                >
                  <div className="benefit-icon">{benefit.icon}</div>
                  <div className="benefit-content">
                    <h4 className="benefit-title">{benefit.title}</h4>
                    <p className="benefit-description">{benefit.description}</p>
                  </div>
                </div>
              ))}
            </div>

            <div className="startups-cta">
              <a 
                href="https://app.pointwave.ng/auth/register" 
                className="btn btn-primary"
              >
                Get Started Free
              </a>
              <a 
                href="https://app.pointwave.ng/documentation/home" 
                className="btn btn-secondary"
              >
                View Documentation
              </a>
            </div>
          </div>

          <div className="startups-visual">
            <div className="visual-card card-1">
              <div className="card-icon">💳</div>
              <div className="card-text">Virtual Accounts</div>
            </div>
            <div className="visual-card card-2">
              <div className="card-icon">🔄</div>
              <div className="card-text">Instant Transfers</div>
            </div>
            <div className="visual-card card-3">
              <div className="card-icon">📱</div>
              <div className="card-text">Mobile Payments</div>
            </div>
          </div>
        </div>
      </div>
    </section>
  );
};

export default ForStartupsSection;
