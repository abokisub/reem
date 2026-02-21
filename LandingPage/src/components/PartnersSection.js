import React from 'react';
import './PartnersSection.css';
import palmpayLogo from '../assets/palmpay.png';
import kobopointLogo from '../assets/kobopoint.png';
import biaLogo from '../assets/bia.png';

const PartnersSection = () => {
  const partners = [
    {
      name: 'PalmPay',
      description: 'Leading mobile payment platform',
      logo: palmpayLogo
    },
    {
      name: 'Kobopoint',
      description: 'Licensed Payment Service Bank',
      logo: kobopointLogo
    },
    {
      name: 'BIA',
      description: 'Digital Transport Services',
      logo: biaLogo
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
              <div className="partner-logo">
                <img src={partner.logo} alt={partner.name} />
              </div>
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
