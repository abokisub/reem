import React from 'react';
import Navbar from '../components/Navbar';
import Footer from '../components/Footer';
import HeroSection from '../components/HeroSection';
import PartnersSection from '../components/PartnersSection';
import FeaturesSection from '../components/FeaturesSection';
import ForStartupsSection from '../components/ForStartupsSection';
import ComingFeaturesSection from '../components/ComingFeaturesSection';
import './HomePage.css';

const HomePage = () => {
  return (
    <div className="home-page">
      <Navbar />
      <main>
        <HeroSection />
        <PartnersSection />
        <FeaturesSection />
        <ForStartupsSection />
        <ComingFeaturesSection />
      </main>
      <Footer />
    </div>
  );
};

export default HomePage;
