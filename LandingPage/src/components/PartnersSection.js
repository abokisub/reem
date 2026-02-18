import React from 'react';
import './PartnersSection.css';

const PartnersSection = () => {
  const partners = [
    {
      name: 'PalmPay',
      description: 'Leading mobile payment platform',
      logo: '🏦'
    },
    {
      name: '9PSB',
      description: 'Licensed Payment Service Bank',
      logo: '🏛️'
    },
    {
      name: 'ADE',
      description: 'Advanced Digital Exchange',
      logo: '💳'
    }
  ];

  return (
    <section className="partners-section">
      <div className="container">
        <div className="section-header">
          <h2 className="section-title">Trusted Partners</h2>
          <p className="section-subtitle">
            Powered by industry-leading financial institutions
          </p>
        </div>
        
        <div className="partners-grid">
          {partners.map((partner, index) => (
            <div 
              key={index} 
              className="partner-card"
              style={{ animationDelay: `${index * 0.1}s` }}
            >
              <div className="partner-logo">{partner.logo}</div>
              <h3 className="partner-name">{partner.name}</h3>
              <p className="partner-description">{partner.description}</p>
            </div>
          ))}
        </div>
      </div>
    </section>
  );
};

export default PartnersSection;
