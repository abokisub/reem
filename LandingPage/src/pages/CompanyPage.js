import React from 'react';
import Navbar from '../components/Navbar';
import Footer from '../components/Footer';
import './CompanyPage.css';

const CompanyPage = () => {
  return (
    <div className="company-page">
      <Navbar />
      <main>
        <section className="company-hero">
          <div className="container">
            <h1 className="page-title">About PointWave</h1>
            <p className="page-subtitle">
              Building the future of digital payments in Nigeria
            </p>
          </div>
        </section>

        <section className="company-story">
          <div className="container">
            <div className="story-content">
              <div className="story-text">
                <h2>Our Story</h2>
                <p>
                  PointWave Digital Innovations is a leading payment gateway platform 
                  dedicated to simplifying digital transactions for businesses across Nigeria. 
                  We provide secure, fast, and reliable payment solutions that empower 
                  startups and established businesses to accept payments seamlessly.
                </p>
                <p>
                  Founded with a vision to democratize access to financial technology, 
                  we've built a platform that combines cutting-edge security with 
                  developer-friendly APIs, making it easy for any business to integrate 
                  payment processing into their operations.
                </p>
              </div>
              <div className="story-visual">
                <div className="visual-element">🚀</div>
              </div>
            </div>
          </div>
        </section>

        <section className="company-mission">
          <div className="container">
            <div className="mission-grid">
              <div className="mission-card">
                <div className="mission-icon">🎯</div>
                <h3>Our Mission</h3>
                <p>
                  To provide accessible, secure, and innovative payment solutions 
                  that enable businesses of all sizes to thrive in the digital economy.
                </p>
              </div>
              <div className="mission-card">
                <div className="mission-icon">👁️</div>
                <h3>Our Vision</h3>
                <p>
                  To become Nigeria's most trusted payment gateway platform, 
                  powering millions of transactions and supporting business growth.
                </p>
              </div>
              <div className="mission-card">
                <div className="mission-icon">💎</div>
                <h3>Our Values</h3>
                <p>
                  Security, transparency, innovation, and customer success drive 
                  everything we do at PointWave.
                </p>
              </div>
            </div>
          </div>
        </section>

        <section className="company-location">
          <div className="container">
            <h2 className="section-title">Get in Touch</h2>
            <div className="location-info">
              <div className="info-item">
                <div className="info-icon">📍</div>
                <div className="info-content">
                  <h4>Location</h4>
                  <p>Kano State, Nigeria</p>
                </div>
              </div>
              <div className="info-item">
                <div className="info-icon">📧</div>
                <div className="info-content">
                  <h4>Email</h4>
                  <p>support@pointwave.ng</p>
                </div>
              </div>
              <div className="info-item">
                <div className="info-icon">📞</div>
                <div className="info-content">
                  <h4>Phone</h4>
                  <p>02014542876</p>
                </div>
              </div>
            </div>
          </div>
        </section>
      </main>
      <Footer />
    </div>
  );
};

export default CompanyPage;
